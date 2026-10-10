<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Usable as a PHP attribute on a DTO property (#[CloudflareTurnstile]) as well as through the
 * TurnstileType form field, which adds it for you.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class CloudflareTurnstile extends Constraint
{
    public function __construct(
        public string $missingResponseMessage = 'turnstile.missing_response',
        public string $verificationFailedMessage = 'turnstile.verification_failed',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(groups: $groups, payload: $payload);
    }
}
