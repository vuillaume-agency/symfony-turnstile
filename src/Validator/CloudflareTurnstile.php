<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Validator;

use Symfony\Component\Validator\Constraint;

final class CloudflareTurnstile extends Constraint
{
    public function __construct(
        public string $missingResponseMessage = 'turnstile.missing_response',
        public string $verificationFailedMessage = 'turnstile.verification_failed',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
