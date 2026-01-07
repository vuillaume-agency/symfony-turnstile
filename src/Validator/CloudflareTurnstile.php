<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Validator;

use Symfony\Component\Validator\Constraint;

final class CloudflareTurnstile extends Constraint
{
    /**
     * @var string
     */
    public $message = 'invalid_turnstile';
}
