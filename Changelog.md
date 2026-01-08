# Changelog

## 1.0.6 (2026-01-08)

### Added

- Full test coverage for all components
- CloudflareTurnstile constraint tests
- Configuration and Extension tests
- TurnstileType disabled mode tests
- CI status badge in README

## 1.0.5 (2026-01-08)

### Added

- Integration tests for TurnstileType form submission and validation

### Fixed

- Fix constraints option definition (use lazy default instead of normalizer)

## 1.0.4 (2026-01-08)

### Fixed

- Fix form validation crash (constraints must be an array)
- Fix Constraint parent constructor call

## 1.0.3 (2026-01-08)

### Fixed

- Fix Symfony 7.4 deprecation warning for Constraint class property initialization

## 1.0.2 (2026-01-08)

### Added

- Migration guide from `pixelopen/cloudflare-turnstile-bundle`

## 1.0.1 (2026-01-08)

### Changed

- Zero-config setup: bundle now reads from `TURNSTILE_KEY` and `TURNSTILE_SECRET` env vars by default
- No YAML configuration file required for basic usage
- Simplified installation to 3 steps
- Recommend test keys over `enable: false` for development

## 1.0.0 (2026-01-07)

First release of `vuillaume-agency/symfony-turnstile`, a fork of `pixelopen/cloudflare-turnstile-bundle`.

### Breaking Changes

- Requires PHP >= 8.2
- Requires Symfony >= 7.4
- New namespace: `VuillaumeAgency\TurnstileBundle`
- New config key: `vuillaume_agency_turnstile`

### Added

- Support for Symfony 8.0
- 6 languages for error messages (en, fr, es, de, it, pt)
- Two distinct error messages (missing_response, verification_failed)
- Form options for custom error messages
- TurnstileHttpClientInterface for better testability
- Validator unit tests

### Changed

- Modernized code with PHP 8.2+ features (readonly, constructor promotion)
- Upgraded dev dependencies (PHPUnit 12, PHPStan 2, PHP-CS-Fixer)

---

## Previous releases (pixelopen/cloudflare-turnstile-bundle)

### 0.4.1 (2024-09-30)

- Fix deprecation from Extension class

### 0.4.0 (2024-05-31)

- Decouple HTTP client and validator so we can verify responses outside Forms
- Fix TreeBuilder name

### 0.3.0 (2023-12-08)

- Allow Symfony 7

### 0.2.0 (2023-10-31)

- Add enable option

### 0.1.4 (2023-07-07)

- Prefer defer to async to improve page speed
- Add codacy
- Add security checker on github actions
- Add phpstan on github actions
- Add github actions

### 0.1.3 (2022-12-05)

- Add explicit return type to avoid deprecation warnings on Symfony 6.2

### 0.1.2 (2022-10-22)

- Complete documentation
- Remove recipe (add recipe into recipes-contrib)

### 0.1.1 (2022-10-22)

- Add recipe
- Change namespace
- Fix installation on readme file

### 0.1.0 (2022-10-22)

- First release
