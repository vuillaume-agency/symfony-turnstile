# Symfony Turnstile Bundle

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-green)](https://php.net/)
[![Minimum Symfony Version](https://img.shields.io/badge/symfony-%3E%3D%207.4-green)](https://symfony.com)

A Symfony bundle to integrate [Cloudflare Turnstile](https://www.cloudflare.com/products/turnstile/) on your forms. Turnstile is a privacy-preserving alternative to CAPTCHA that doesn't require user interaction.

![Cloudflare Turnstile for Symfony Form](screenshot.png)

## Why Turnstile over reCAPTCHA?

Cloudflare Turnstile offers significant advantages over Google reCAPTCHA:

| Feature | Turnstile | reCAPTCHA |
|---------|-----------|-----------|
| **User experience** | Invisible, no puzzles to solve | Often requires solving image puzzles |
| **GDPR compliance** | Privacy-first, no tracking cookies | Transfers data to Google (US), requires explicit consent |
| **Data collection** | Minimal, no personal data stored | Collects browsing behavior and device data |
| **Cookie consent** | No cookie banner required | Requires cookie consent under GDPR |
| **Performance** | Lightweight (~20KB) | Heavier (~400KB+) |
| **Cost** | Free for unlimited use | Free tier limited, paid for high volume |

### GDPR considerations

For European websites, reCAPTCHA poses compliance challenges:
- Data is transferred to Google servers in the US
- Requires explicit user consent before loading
- Must be declared in your privacy policy
- Several EU data protection authorities have raised concerns

Turnstile is designed with privacy in mind and doesn't require cookie consent banners, making it an excellent choice for GDPR-compliant websites.

## About this fork

This bundle is a fork of [pixelopen/cloudflare-turnstile-bundle](https://github.com/Pixel-Open/cloudflare-turnstile-bundle), originally created by Pixel Développement.

**Why we forked it:**
- The original package was not actively maintained for Symfony 7.4+ and 8.0
- We needed modern PHP 8.2+ features and up-to-date dependencies
- We wanted to add improved error messages with multi-language support

**Our commitment:**
- Active maintenance for Symfony 7.4 LTS and 8.x
- Regular updates and security patches
- Community contributions welcome

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | >= 8.2 |
| Symfony | >= 7.4 |

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

### Step 3: Configure the bundle

Create `config/packages/vuillaume_agency_turnstile.yaml`:

```yaml
vuillaume_agency_turnstile:
    key: '%env(TURNSTILE_KEY)%'
    secret: '%env(TURNSTILE_SECRET)%'
    enable: true
```

| Option | Type | Required | Description |
|--------|------|----------|-------------|
| `key` | string | Yes | Your Turnstile site key (public) |
| `secret` | string | Yes | Your Turnstile secret key (private) |
| `enable` | boolean | No | Enable/disable validation (default: `true`). Set to `false` to bypass validation during development. |

### Step 4: Add your Cloudflare credentials

Get your keys from the [Cloudflare Dashboard](https://dash.cloudflare.com/?to=/:account/turnstile) and add them to your `.env` file:

```env
TURNSTILE_KEY="your-site-key"
TURNSTILE_SECRET="your-secret-key"
```

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
            ->add('security', TurnstileType::class, [
                'label' => false,
            ])
            ->add('submit', SubmitType::class);
    }
}
```

### Turnstile widget options

You can customize the Turnstile widget using the `attr` option:

```php
->add('security', TurnstileType::class, [
    'label' => false,
    'attr' => [
        'data-theme' => 'dark',      // 'light', 'dark', or 'auto'
        'data-size' => 'compact',    // 'normal' or 'compact'
        'data-action' => 'contact',  // Custom action name for analytics
    ],
])
```

See [Cloudflare Turnstile documentation](https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/) for all available options.

## Translations

The bundle provides error messages in **6 languages** out of the box:

| Language | Code |
|----------|------|
| English | `en` |
| French | `fr` |
| Spanish | `es` |
| German | `de` |
| Italian | `it` |
| Portuguese | `pt` |

### Error messages

Two distinct error messages are provided:

| Key | Description |
|-----|-------------|
| `turnstile.missing_response` | Displayed when the user hasn't completed the Turnstile challenge |
| `turnstile.verification_failed` | Displayed when the server-side verification fails |

### Customizing error messages

#### Option 1: Form options (Recommended)

Pass custom messages directly as form options:

```php
->add('security', TurnstileType::class, [
    'label' => false,
    'missing_response_message' => 'Please verify you are human.',
    'verification_failed_message' => 'Verification failed, please try again.',
])
```

| Option | Description |
|--------|-------------|
| `missing_response_message` | Message when user hasn't completed the challenge |
| `verification_failed_message` | Message when server-side verification fails |

#### Option 2: Override translations

Create your own translation file in `translations/validators.{locale}.yaml`:

```yaml
# translations/validators.en.yaml
turnstile.missing_response: Please verify you are human.
turnstile.verification_failed: Verification failed. Please try again.
```

## Testing

During development, use Cloudflare's test credentials:

### Test site keys

| Site key | Behavior |
|----------|----------|
| `1x00000000000000000000AA` | Always passes |
| `2x00000000000000000000AB` | Always blocks |
| `3x00000000000000000000FF` | Forces an interactive challenge |

### Test secret keys

| Secret key | Behavior |
|------------|----------|
| `1x0000000000000000000000000000000AA` | Always passes |
| `2x0000000000000000000000000000000AA` | Always fails |
| `3x0000000000000000000000000000000AA` | Returns "token already spent" error |

### Disabling validation in development

Set `enable: false` in your config to bypass Turnstile validation:

```yaml
# config/packages/dev/vuillaume_agency_turnstile.yaml
vuillaume_agency_turnstile:
    key: '%env(TURNSTILE_KEY)%'
    secret: '%env(TURNSTILE_SECRET)%'
    enable: false
```

## License

The MIT License (MIT). See [LICENSE](LICENSE) for more information.
