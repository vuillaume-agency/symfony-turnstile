<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Validator;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use VuillaumeAgency\TurnstileBundle\Http\TurnstileHttpClientInterface;

final class CloudflareTurnstileValidator extends ConstraintValidator
{
    public function __construct(
        private readonly bool $enable,
        private readonly RequestStack $requestStack,
        private readonly TurnstileHttpClientInterface $turnstileHttpClient,
    ) {
    }

    /**
     * Symfony 8.1 entry point. Symfony 7.4 goes through initialize() + validate() below.
     */
    public function validateInContext(mixed $value, Constraint $constraint, ExecutionContextInterface $context): void
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
            $context->buildViolation($constraint->missingResponseMessage)
                ->addViolation();

            return;
        }

        if (false === $this->turnstileHttpClient->verifyResponse($turnstileResponse)) {
            $context->buildViolation($constraint->verificationFailedMessage)
                ->addViolation();
        }
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        $this->validateInContext($value, $constraint, $this->context);
    }
}
