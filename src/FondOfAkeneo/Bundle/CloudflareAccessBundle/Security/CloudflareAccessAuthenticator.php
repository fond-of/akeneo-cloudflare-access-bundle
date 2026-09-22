<?php

namespace FondOfAkeneo\Bundle\CloudflareAccessBundle\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Trusts an already-verified Cloudflare Access login (JWT) to skip Akeneo's
 * own login form. Deliberately does NOT auto-create Akeneo users - only
 * emails that already have an existing account can use this path. Any
 * failure here silently falls through to the normal form_login instead of
 * blocking the request, since Cloudflare Access is what actually gated
 * network access in the first place.
 */
class CloudflareAccessAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserProviderInterface $userProvider,
        private readonly CloudflareJwtVerifier $jwtVerifier,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if ($request->attributes->get('_route') === 'pim_user_security_logout') {
            return false;
        }

        return $request->cookies->has('CF_Authorization') || $request->headers->has('Cf-Access-Jwt-Assertion');
    }

    public function authenticate(Request $request): Passport
    {
        $jwt = $request->headers->get('Cf-Access-Jwt-Assertion') ?? $request->cookies->get('CF_Authorization');

        try {
            $email = $this->jwtVerifier->verifyAndGetEmail($jwt);
        } catch (\Throwable $e) {
            throw new AuthenticationException('Cloudflare Access token rejected: ' . $e->getMessage(), 0, $e);
        }

        return new SelfValidatingPassport(
            new UserBadge($email, function (string $identifier) {
                try {
                    return $this->userProvider->loadUserByIdentifier($identifier);
                } catch (UserNotFoundException $e) {
                    $this->logger->info(
                        'Cloudflare Access login for an email with no matching Akeneo user - falling back to normal login.',
                        ['email' => $identifier]
                    );
                    throw $e;
                }
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // let the request continue to whatever page was originally asked for
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null; // fall through to Akeneo's normal form_login instead of blocking the request
    }
}
