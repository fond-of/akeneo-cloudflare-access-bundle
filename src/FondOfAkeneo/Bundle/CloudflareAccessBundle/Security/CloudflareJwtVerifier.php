<?php

namespace FondOfAkeneo\Bundle\CloudflareAccessBundle\Security;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Verifies a Cloudflare Access JWT (from the CF_Authorization cookie or the
 * Cf-Access-Jwt-Assertion header) against Cloudflare's own public keys, and
 * extracts the authenticated user's email. Throws on any failure - callers
 * decide what that means for the request (fall back to normal login, etc.).
 */
class CloudflareJwtVerifier
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly string $teamDomain,
        private readonly string $applicationAudience,
    ) {
    }

    public function verifyAndGetEmail(string $jwt): string
    {
        $keys = $this->fetchJwks();

        $decoded = JWT::decode($jwt, JWK::parseKeySet($keys));

        $audiences = is_array($decoded->aud ?? null) ? $decoded->aud : [$decoded->aud ?? null];
        if (!in_array($this->applicationAudience, $audiences, true)) {
            throw new \RuntimeException('Cloudflare Access token audience does not match this application.');
        }

        if (empty($decoded->email)) {
            throw new \RuntimeException('Cloudflare Access token has no email claim.');
        }

        return $decoded->email;
    }

    private function fetchJwks(): array
    {
        return $this->cache->get('cloudflare_access_jwks', function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            $certsUrl = sprintf('https://%s.cloudflareaccess.com/cdn-cgi/access/certs', $this->teamDomain);
            $response = $this->httpClient->request('GET', $certsUrl);

            return $response->toArray();
        });
    }
}
