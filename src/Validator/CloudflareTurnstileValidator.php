<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Validator;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use VuillaumeAgency\TurnstileBundle\Http\TurnstileHttpClientInterface;

final class CloudflareTurnstileValidator extends ConstraintValidator
{
    public function __construct(
        private readonly bool $enable,
        private readonly RequestStack $requestStack,
        private readonly TurnstileHttpClientInterface $turnstileHttpClient,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CloudflareTurnstile) {
            return;
        }

        if (false === $this->enable) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        \assert(null !== $request);
        $turnstileResponse = (string) $request->request->get('cf-turnstile-response');

        if ('' === $turnstileResponse) {
            $this->context->buildViolation($constraint->missingResponseMessage)
                ->addViolation();

            return;
        }

        if (false === $this->turnstileHttpClient->verifyResponse($turnstileResponse)) {
            $this->context->buildViolation($constraint->verificationFailedMessage)
                ->addViolation();
        }
    }
}
