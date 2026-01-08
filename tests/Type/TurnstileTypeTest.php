<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Validation;
use VuillaumeAgency\TurnstileBundle\Http\TurnstileHttpClientInterface;
use VuillaumeAgency\TurnstileBundle\Type\TurnstileType;
use VuillaumeAgency\TurnstileBundle\Validator\CloudflareTurnstile;
use VuillaumeAgency\TurnstileBundle\Validator\CloudflareTurnstileValidator;

final class TurnstileTypeTest extends TestCase
{
    private FormFactoryInterface $factory;
    private FormFactoryInterface $factoryWithValidation;
    private RequestStack $requestStack;
    private TurnstileHttpClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->httpClient = $this->createMock(TurnstileHttpClientInterface::class);

        // Factory without validation (for testing form structure)
        $this->factory = Forms::createFormFactoryBuilder()
            ->addType(new TurnstileType('test-site-key', true))
            ->getFormFactory();

        // Factory with validation (for testing form submission)
        $constraintValidatorFactory = new TestConstraintValidatorFactory(
            $this->requestStack,
            $this->httpClient
        );

        $validator = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory($constraintValidatorFactory)
            ->getValidator();

        $this->factoryWithValidation = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->addType(new TurnstileType('test-site-key', true))
            ->getFormFactory();
    }

    public function testFormCanBeCreated(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('email', TextType::class)
            ->add('captcha', TurnstileType::class)
            ->getForm();

        self::assertTrue($form->has('captcha'));
    }

    public function testConstraintsAreArray(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('captcha', TurnstileType::class)
            ->getForm();

        $captchaConfig = $form->get('captcha')->getConfig();
        $constraints = $captchaConfig->getOption('constraints');

        self::assertIsArray($constraints, 'Constraints must be an array');
        self::assertCount(1, $constraints);
        self::assertInstanceOf(CloudflareTurnstile::class, $constraints[0]);
    }

    public function testConstraintsWithCustomMessages(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('captcha', TurnstileType::class, [
                'missing_response_message' => 'Custom missing message',
                'verification_failed_message' => 'Custom failed message',
            ])
            ->getForm();

        $constraints = $form->get('captcha')->getConfig()->getOption('constraints');

        self::assertInstanceOf(CloudflareTurnstile::class, $constraints[0]);
        self::assertSame('Custom missing message', $constraints[0]->missingResponseMessage);
        self::assertSame('Custom failed message', $constraints[0]->verificationFailedMessage);
    }

    public function testConstraintHasDefaultMessages(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('captcha', TurnstileType::class)
            ->getForm();

        $constraints = $form->get('captcha')->getConfig()->getOption('constraints');

        self::assertSame('turnstile.missing_response', $constraints[0]->missingResponseMessage);
        self::assertSame('turnstile.verification_failed', $constraints[0]->verificationFailedMessage);
    }

    public function testConstraintGroupsAreSet(): void
    {
        $constraint = new CloudflareTurnstile();

        self::assertIsArray($constraint->groups);
        self::assertContains('Default', $constraint->groups);
    }

    public function testFormViewHasKey(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('captcha', TurnstileType::class)
            ->getForm();

        $view = $form->get('captcha')->createView();

        self::assertArrayHasKey('key', $view->vars);
        self::assertSame('test-site-key', $view->vars['key']);
    }

    public function testFormViewHasEnable(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('captcha', TurnstileType::class)
            ->getForm();

        $view = $form->get('captcha')->createView();

        self::assertArrayHasKey('enable', $view->vars);
        self::assertTrue($view->vars['enable']);
    }

    public function testFormIsNotMapped(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('captcha', TurnstileType::class)
            ->getForm();

        $captchaConfig = $form->get('captcha')->getConfig();

        self::assertFalse($captchaConfig->getMapped());
    }

    public function testFormSubmission(): void
    {
        $request = new Request([], ['cf-turnstile-response' => 'test-response']);
        $this->requestStack->push($request);

        $this->httpClient
            ->method('verifyResponse')
            ->willReturn(true);

        $form = $this->factoryWithValidation->createBuilder(FormType::class)
            ->add('email', TextType::class)
            ->add('captcha', TurnstileType::class)
            ->getForm();

        $form->submit([
            'email' => 'test@example.com',
            'captcha' => '',
        ]);

        self::assertTrue($form->isSubmitted());
        self::assertTrue($form->isValid());
    }

    public function testFormValidationFailsWithMissingResponse(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $form = $this->factoryWithValidation->createBuilder(FormType::class)
            ->add('email', TextType::class)
            ->add('captcha', TurnstileType::class)
            ->getForm();

        $form->submit([
            'email' => 'test@example.com',
            'captcha' => '',
        ]);

        self::assertTrue($form->isSubmitted());
        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('captcha')->getErrors());
    }

    public function testFormValidationFailsWithInvalidResponse(): void
    {
        $request = new Request([], ['cf-turnstile-response' => 'invalid-response']);
        $this->requestStack->push($request);

        $this->httpClient
            ->method('verifyResponse')
            ->willReturn(false);

        $form = $this->factoryWithValidation->createBuilder(FormType::class)
            ->add('email', TextType::class)
            ->add('captcha', TurnstileType::class)
            ->getForm();

        $form->submit([
            'email' => 'test@example.com',
            'captcha' => '',
        ]);

        self::assertTrue($form->isSubmitted());
        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('captcha')->getErrors());
    }
}

final class TestConstraintValidatorFactory implements ConstraintValidatorFactoryInterface
{
    private array $validators = [];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TurnstileHttpClientInterface $httpClient,
    ) {
    }

    public function getInstance(\Symfony\Component\Validator\Constraint $constraint): ConstraintValidatorInterface
    {
        $className = $constraint->validatedBy();

        if (!isset($this->validators[$className])) {
            if (CloudflareTurnstileValidator::class === $className) {
                $this->validators[$className] = new CloudflareTurnstileValidator(
                    true,
                    $this->requestStack,
                    $this->httpClient
                );
            } else {
                $this->validators[$className] = new $className();
            }
        }

        return $this->validators[$className];
    }
}
