<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use VuillaumeAgency\TurnstileBundle\Http\TurnstileHttpClientInterface;
use VuillaumeAgency\TurnstileBundle\Validator\CloudflareTurnstile;
use VuillaumeAgency\TurnstileBundle\Validator\CloudflareTurnstileValidator;

final class CloudflareTurnstileValidatorTest extends TestCase
{
    private const DUMMY_TURNSTILE_RESPONSE = 'dummy-turnstile-response';

    public function testValidationSkippedWhenDisabled(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects(self::never())->method('getCurrentRequest');

        $httpClient = $this->createMock(TurnstileHttpClientInterface::class);
        $httpClient->expects(self::never())->method('verifyResponse');

        $validator = new CloudflareTurnstileValidator(false, $requestStack, $httpClient);
        $validator->initialize($this->createContextExpectingNoViolation());

        $validator->validate(null, new CloudflareTurnstile());
    }

    public function testValidationFailsWhenResponseEmpty(): void
    {
        $request = new Request();
        $requestStack = $this->createRequestStack($request);

        $httpClient = $this->createMock(TurnstileHttpClientInterface::class);
        $httpClient->expects(self::never())->method('verifyResponse');

        $validator = new CloudflareTurnstileValidator(true, $requestStack, $httpClient);
        $validator->initialize($this->createContextExpectingViolation('turnstile.missing_response'));

        $validator->validate(null, new CloudflareTurnstile());
    }

    public function testValidationFailsWhenHttpClientReturnsFalse(): void
    {
        $request = new Request([], [
            'cf-turnstile-response' => self::DUMMY_TURNSTILE_RESPONSE,
        ]);
        $requestStack = $this->createRequestStack($request);

        $httpClient = $this->createMock(TurnstileHttpClientInterface::class);
        $httpClient
            ->expects(self::once())
            ->method('verifyResponse')
            ->with(self::DUMMY_TURNSTILE_RESPONSE)
            ->willReturn(false);

        $validator = new CloudflareTurnstileValidator(true, $requestStack, $httpClient);
        $validator->initialize($this->createContextExpectingViolation('turnstile.verification_failed'));

        $validator->validate(null, new CloudflareTurnstile());
    }

    public function testValidationPassesWhenHttpClientReturnsTrue(): void
    {
        $request = new Request([], [
            'cf-turnstile-response' => self::DUMMY_TURNSTILE_RESPONSE,
        ]);
        $requestStack = $this->createRequestStack($request);

        $httpClient = $this->createMock(TurnstileHttpClientInterface::class);
        $httpClient
            ->expects(self::once())
            ->method('verifyResponse')
            ->with(self::DUMMY_TURNSTILE_RESPONSE)
            ->willReturn(true);

        $validator = new CloudflareTurnstileValidator(true, $requestStack, $httpClient);
        $validator->initialize($this->createContextExpectingNoViolation());

        $validator->validate(null, new CloudflareTurnstile());
    }

    private function createRequestStack(Request $request): RequestStack
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        return $requestStack;
    }

    private function createContextExpectingViolation(string $expectedMessage): ExecutionContextInterface
    {
        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder
            ->expects(self::once())
            ->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context
            ->expects(self::once())
            ->method('buildViolation')
            ->with($expectedMessage)
            ->willReturn($violationBuilder);

        return $context;
    }

    private function createContextExpectingNoViolation(): ExecutionContextInterface
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::never())->method('buildViolation');

        return $context;
    }
}
