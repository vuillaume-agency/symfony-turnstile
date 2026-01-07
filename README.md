# Symfony Turnstile Bundle

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-green)](https://php.net/)
[![Minimum Symfony Version](https://img.shields.io/badge/symfony-%3E%3D%207.4-green)](https://symfony.com)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

A Symfony bundle to integrate [Cloudflare Turnstile](https://www.cloudflare.com/products/turnstile/) on your forms. Turnstile is a privacy-preserving alternative to CAPTCHA that doesn't require user interaction.

## Features

- **Zero user friction** — No puzzles, no clicking on traffic lights
- **GDPR-friendly** — No cookie consent required, privacy-first design
- **6 languages included** — English, French, Spanish, German, Italian, Portuguese
- **Symfony 8 ready** — Full support for Symfony 7.4 LTS and 8.x
- **Customizable messages** — Override error messages via form options or translations
- **Easy theming** — Light, dark, or auto theme support
- **Fully tested** — PHPUnit tests and PHPStan static analysis

## Quick Start

```bash
composer require vuillaume-agency/symfony-turnstile
```

```env
# .env
TURNSTILE_KEY="your-site-key"
TURNSTILE_SECRET="your-secret-key"
```

```php
// In your form
->add('captcha', TurnstileType::class)
```

Get your free keys at [Cloudflare Dashboard](https://dash.cloudflare.com/?to=/:account/turnstile).

## Why Turnstile over reCAPTCHA?

| Feature | Turnstile | reCAPTCHA |
|---------|:---------:|:---------:|
| Usually invisible (no puzzles) | Yes | Yes (v3 only) |
| GDPR compliant by design | Yes | No (requires consent) |
| Uses cookies | No | Yes |
| Data used for advertising | No | Unclear |
| Script size | ~30KB | ~80KB |
| Free unlimited | Yes | Yes (with limits) |

### GDPR considerations

For European websites, reCAPTCHA poses significant compliance challenges:

- **Cookies**: reCAPTCHA sets cookies that require user consent under GDPR
- **Data transfer**: User data is sent to Google servers in the US
- **Consent required**: Must obtain explicit consent before loading reCAPTCHA
- **Fines**: French DPA (CNIL) has fined companies for improper reCAPTCHA implementation

Turnstile is designed with privacy in mind:

- **No cookies**: Doesn't set any cookies, no consent banner needed
- **Minimal data**: Only collects what's necessary for bot detection (IP, TLS fingerprint)
- **No advertising**: Cloudflare explicitly prohibits using collected data for ads or tracking
- **GDPR-ready**: Can be used without additional consent mechanisms

## About this fork

This bundle is a fork of [pixelopen/cloudflare-turnstile-bundle](https://github.com/Pixel-Open/cloudflare-turnstile-bundle), originally created by Pixel Développement.

**Why we forked:**
- The original package was not maintained for Symfony 7.4+ and 8.0
- We needed modern PHP 8.2+ features and up-to-date dependencies
- We added improved error messages with multi-language support

**Our commitment:**
- Active maintenance for Symfony 7.4 LTS and 8.x
- Regular updates and security patches
- Community contributions welcome

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP         | >= 8.2  |
| Symfony     | >= 7.4  |

## Installation

### Step 1: Install the package

```bash
composer require vuillaume-agency/symfony-turnstile
```

### Step 2: Register the bundle

Add the bundle to your `config/bundles.php`:

```php
return [
    // ...
    VuillaumeAgency\TurnstileBundle\VuillaumeAgencyTurnstileBundle::class => ['all' => true],
];
```

### Step 3: Add your Cloudflare credentials

Get your keys from the [Cloudflare Dashboard](https://dash.cloudflare.com/?to=/:account/turnstile) and add them to your `.env` file:

```env
TURNSTILE_KEY="your-site-key"
TURNSTILE_SECRET="your-secret-key"
```

That's it! The bundle automatically reads from these environment variables.

### Optional: Custom configuration

If you need to customize the bundle, create `config/packages/vuillaume_agency_turnstile.yaml`:

```yaml
vuillaume_agency_turnstile:
    key: '%env(TURNSTILE_KEY)%'      # Default: reads from TURNSTILE_KEY env var
    secret: '%env(TURNSTILE_SECRET)%' # Default: reads from TURNSTILE_SECRET env var
    enable: true                      # Default: true
```

| Option   | Type    | Default                      | Description |
|----------|---------|------------------------------|-------------|
| `key`    | string  | `%env(TURNSTILE_KEY)%`       | Your Turnstile site key (public) |
| `secret` | string  | `%env(TURNSTILE_SECRET)%`    | Your Turnstile secret key (private) |
| `enable` | boolean | `true`                       | Enable/disable validation |

## Usage

### Adding Turnstile to a form

Use `TurnstileType` in your form builder:

```php
<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use VuillaumeAgency\TurnstileBundle\Type\TurnstileType;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class)
            ->add('message', TextareaType::class)
            ->add('captcha', TurnstileType::class, [
                'label' => false,
            ])
            ->add('submit', SubmitType::class);
    }
}
```

### Widget customization

Customize the Turnstile widget appearance:

```php
->add('captcha', TurnstileType::class, [
    'label' => false,
    'attr' => [
        'data-theme' => 'dark',      // 'light', 'dark', or 'auto'
        'data-size' => 'compact',    // 'normal' or 'compact'
        'data-action' => 'contact',  // Custom action name for analytics
    ],
])
```

See [Cloudflare Turnstile documentation](https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/) for all available options.

### Custom error messages

Pass custom messages directly as form options:

```php
->add('captcha', TurnstileType::class, [
    'label' => false,
    'missing_response_message' => 'Please verify you are human.',
    'verification_failed_message' => 'Verification failed, please try again.',
])
```

Or override translations in `translations/validators.{locale}.yaml`:

```yaml
turnstile.missing_response: Please verify you are human.
turnstile.verification_failed: Verification failed. Please try again.
```

## Translations

The bundle provides error messages in **6 languages**:

| Language   | Code |
|------------|------|
| English    | `en` |
| French     | `fr` |
| Spanish    | `es` |
| German     | `de` |
| Italian    | `it` |
| Portuguese | `pt` |

## Migrating from pixelopen/cloudflare-turnstile-bundle

This bundle is a modernized fork. Here's how to migrate:

### Step 1: Replace the package

```bash
composer remove pixelopen/cloudflare-turnstile-bundle
composer require vuillaume-agency/symfony-turnstile
```

### Step 2: Update bundles.php

```php
// config/bundles.php
// Remove:
// PixelOpen\CloudflareTurnstileBundle\PixelOpenCloudflareTurnstileBundle::class => ['all' => true],

// Add:
VuillaumeAgency\TurnstileBundle\VuillaumeAgencyTurnstileBundle::class => ['all' => true],
```

### Step 3: Update configuration

```bash
# Rename config file (if you have one)
mv config/packages/pixel_open_cloudflare_turnstile.yaml config/packages/vuillaume_agency_turnstile.yaml
```

Update the config key in the file:

```yaml
# Before:
# pixel_open_cloudflare_turnstile:
#     key: '%env(TURNSTILE_KEY)%'
#     secret: '%env(TURNSTILE_SECRET)%'

# After (or just delete the file - env vars work by default now):
vuillaume_agency_turnstile:
    key: '%env(TURNSTILE_KEY)%'
    secret: '%env(TURNSTILE_SECRET)%'
```

### Step 4: Update imports in your code

Find and replace in your project:

| Old | New |
|-----|-----|
| `PixelOpen\CloudflareTurnstileBundle\Type\TurnstileType` | `VuillaumeAgency\TurnstileBundle\Type\TurnstileType` |
| `PixelOpen\CloudflareTurnstileBundle\Validator\CloudflareTurnstile` | `VuillaumeAgency\TurnstileBundle\Validator\CloudflareTurnstile` |

```bash
# Quick find/replace with sed (Linux/macOS)
find src -name "*.php" -exec sed -i '' 's/PixelOpen\\CloudflareTurnstileBundle/VuillaumeAgency\\TurnstileBundle/g' {} +
```

### Step 5: Clear cache

```bash
php bin/console cache:clear
```

## Migrating from reCAPTCHA

Switching from Google reCAPTCHA is straightforward:

1. Remove your reCAPTCHA bundle and configuration
2. Install this bundle (see [Installation](#installation))
3. Replace `RecaptchaType` with `TurnstileType` in your forms
4. Remove reCAPTCHA from your cookie consent banner
5. Update your privacy policy (simpler now!)

No changes needed in your controllers — validation works the same way.

## Testing

During development, use Cloudflare's test credentials instead of real keys. This is the **recommended approach** as it keeps validation active while providing predictable behavior.

### Test site keys

| Site key                   | Behavior                        |
|----------------------------|---------------------------------|
| `1x00000000000000000000AA` | Always passes (recommended)     |
| `2x00000000000000000000AB` | Always blocks                   |
| `3x00000000000000000000FF` | Forces an interactive challenge |

### Test secret keys

| Secret key                            | Behavior                       |
|---------------------------------------|--------------------------------|
| `1x0000000000000000000000000000000AA` | Always passes (recommended)    |
| `2x0000000000000000000000000000000AA` | Always fails                   |
| `3x0000000000000000000000000000000AA` | Returns "token already spent"  |

### Example dev configuration

```env
# .env.local (for development)
TURNSTILE_KEY="1x00000000000000000000AA"
TURNSTILE_SECRET="1x0000000000000000000000000000000AA"
```

### Alternative: Disabling validation

You can also disable validation entirely, but using test keys is preferred as it keeps your code paths consistent between environments:

```yaml
# config/packages/dev/vuillaume_agency_turnstile.yaml
vuillaume_agency_turnstile:
    enable: false
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please ensure tests pass (`vendor/bin/phpunit`) and code follows standards (`vendor/bin/php-cs-fixer fix`).

## License

The MIT License (MIT). See [LICENSE](LICENSE) for more information.
