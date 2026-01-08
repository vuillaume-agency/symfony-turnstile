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
use VuillaumeAgency\TurnstileBundle\Validator\CloudflareTurnstileValidator;

final class TurnstileTypeDisabledTest extends TestCase
{
    private FormFactoryInterface $factory;
    private FormFactoryInterface $factoryWithValidation;
    private RequestStack $requestStack;
    private TurnstileHttpClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->httpClient = $this->createMock(TurnstileHttpClientInterface::class);

        // Factory with enable=false
        $this->factory = Forms::createFormFactoryBuilder()
            ->addType(new TurnstileType('test-site-key', false))
            ->getFormFactory();

        // Factory with validation and enable=false
        $constraintValidatorFactory = new DisabledTestConstraintValidatorFactory(
            $this->requestStack,
            $this->httpClient
        );

        $validator = Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory($constraintValidatorFactory)
            ->getValidator();

        $this->factoryWithValidation = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->addType(new TurnstileType('test-site-key', false))
            ->getFormFactory();
    }

    public function testFormViewHasEnableFalse(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('captcha', TurnstileType::class)
            ->getForm();

        $view = $form->get('captcha')->createView();

        self::assertArrayHasKey('enable', $view->vars);
        self::assertFalse($view->vars['enable']);
    }

    public function testFormSubmissionPassesWhenDisabled(): void
    {
        // No turnstile response in request
        $request = new Request();
        $this->requestStack->push($request);

        // HTTP client should never be called when disabled
        $this->httpClient
            ->expects(self::never())
            ->method('verifyResponse');

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

    public function testFormViewStillHasKeyWhenDisabled(): void
    {
        $form = $this->factory->createBuilder(FormType::class)
            ->add('captcha', TurnstileType::class)
            ->getForm();

        $view = $form->get('captcha')->createView();

        self::assertArrayHasKey('key', $view->vars);
        self::assertSame('test-site-key', $view->vars['key']);
    }
}

final class DisabledTestConstraintValidatorFactory implements ConstraintValidatorFactoryInterface
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
            if ($className === CloudflareTurnstileValidator::class) {
                // enable=false
                $this->validators[$className] = new CloudflareTurnstileValidator(
                    false,
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
