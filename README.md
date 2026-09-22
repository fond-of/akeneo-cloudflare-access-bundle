# fond-of-akeneo/cloudflare-access-bundle

Symfony security authenticator for Akeneo PIM (Community Edition) that trusts
an already-verified [Cloudflare Access](https://developers.cloudflare.com/cloudflare-one/policies/access/)
JWT to automatically log in an **existing** Akeneo user, instead of showing
Akeneo's own login form a second time after Access has already authenticated
the request.

It does **not** auto-create Akeneo user accounts. If the JWT's `email` claim
doesn't match an existing Akeneo user, this authenticator fails silently and
Akeneo's normal login form is shown instead - it never blocks a request on
its own.

## Requirements

- Akeneo PIM CE running behind Cloudflare Access (the request must already be
  authenticated by Access before it reaches Akeneo - this bundle does not
  itself protect anything, it only trusts what Access already verified).
- The application must be reachable at a hostname protected by a Cloudflare
  Access "self-hosted" application, so that Access injects either the
  `CF_Authorization` cookie or the `Cf-Access-Jwt-Assertion` header.

## Installation

```bash
composer require fond-of-akeneo/cloudflare-access-bundle
```

Register the bundle in `config/bundles.php`:

```php
FondOfAkeneo\Bundle\CloudflareAccessBundle\FondOfAkeneoCloudflareAccessBundle::class => ['all' => true],
```

Configure it in `config/packages/fond_of_akeneo_cloudflare_access.yml`:

> **Akeneo note:** the file extension must be `.yml`, not `.yaml`. Akeneo's
> custom `src/Kernel.php` only globs `config/packages/*.yml` for the project
> config directory, so a `.yaml` file there is silently never loaded at all
> (no error - `debug:config` will just claim the required keys are missing).

```yaml
fond_of_akeneo_cloudflare_access:
  team_domain: "%env(CLOUDFLARE_ACCESS_TEAM_DOMAIN)%" # the <name> in https://<name>.cloudflareaccess.com
  application_audience: "%env(CLOUDFLARE_ACCESS_AUD)%" # Access application's "Application Audience (AUD) Tag"
```

Wire the authenticator into Akeneo's `main` firewall by editing the
project's **existing** `config/packages/security.yml` directly, adding
`custom_authenticators` under the existing `main:` entry:

```yaml
security:
  firewalls:
    main:
      custom_authenticators:
        - FondOfAkeneo\Bundle\CloudflareAccessBundle\Security\CloudflareAccessAuthenticator
      # ... keep the rest of the existing main firewall config as-is
```

> **Don't** put this in a separate config file. Symfony's firewalls config is
> a keyed prototype array, and it does not allow a firewall that's already
> defined (like `main`) to receive additional keys from a _different_ config
> file - you'll get `InvalidConfigurationException: You are not allowed to
define new elements for path "security.firewalls"`.

Set `CLOUDFLARE_ACCESS_TEAM_DOMAIN` and `CLOUDFLARE_ACCESS_AUD` in `.env.local`
(the AUD tag is on the Access application's overview page in the Cloudflare
Zero Trust dashboard).
