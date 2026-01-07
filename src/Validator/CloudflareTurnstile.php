<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Validator;

use Symfony\Component\Validator\Constraint;

final class CloudflareTurnstile extends Constraint
{
    public string $missingResponseMessage = 'turnstile.missing_response';

    public string $verificationFailedMessage = 'turnstile.verification_failed';

    public function __construct(
        ?string $missingResponseMessage = null,
        ?string $verificationFailedMessage = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);

        $this->missingResponseMessage = $missingResponseMessage ?? $this->missingResponseMessage;
        $this->verificationFailedMessage = $verificationFailedMessage ?? $this->verificationFailedMessage;
    }
}
