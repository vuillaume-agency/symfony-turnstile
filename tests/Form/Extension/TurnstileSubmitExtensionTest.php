<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\Form\Extension;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use VuillaumeAgency\TurnstileBundle\Form\Extension\TurnstileSubmitExtension;
use VuillaumeAgency\TurnstileBundle\Type\TurnstileType;

final class TurnstileSubmitExtensionTest extends TestCase
{
    public function testGetExtendedTypes(): void
    {
        $extension = new TurnstileSubmitExtension(true);

        self::assertSame([SubmitType::class], $extension::getExtendedTypes());
    }

    public function testSubmitButtonIsDisabledWhenTurnstileIsPresent(): void
    {
        $factory = $this->createFormFactory(turnstileEnabled: true);

        $form = $factory->createBuilder(FormType::class)
            ->add('email', TextType::class)
            ->add('captcha', TurnstileType::class)
            ->add('submit', SubmitType::class)
            ->getForm();

        $view = $form->createView();

        self::assertArrayHasKey('disabled', $view['submit']->vars['attr']);
        self::assertSame('disabled', $view['submit']->vars['attr']['disabled']);
    }

    public function testSubmitButtonIsNotDisabledWhenTurnstileIsDisabled(): void
    {
        $factory = $this->createFormFactory(turnstileEnabled: false);

        $form = $factory->createBuilder(FormType::class)
            ->add('email', TextType::class)
            ->add('captcha', TurnstileType::class)
            ->add('submit', SubmitType::class)
            ->getForm();

        $view = $form->createView();

        self::assertArrayNotHasKey('disabled', $view['submit']->vars['attr']);
    }

    public function testSubmitButtonIsNotDisabledWhenNoTurnstileField(): void
    {
        $factory = $this->createFormFactory(turnstileEnabled: true);

        $form = $factory->createBuilder(FormType::class)
            ->add('email', TextType::class)
            ->add('submit', SubmitType::class)
            ->getForm();

        $view = $form->createView();

        self::assertArrayNotHasKey('disabled', $view['submit']->vars['attr']);
    }

    public function testMultipleSubmitButtonsAreAllDisabled(): void
    {
        $factory = $this->createFormFactory(turnstileEnabled: true);

        $form = $factory->createBuilder(FormType::class)
            ->add('email', TextType::class)
            ->add('captcha', TurnstileType::class)
            ->add('save', SubmitType::class)
            ->add('saveAndContinue', SubmitType::class)
            ->getForm();

        $view = $form->createView();

        self::assertArrayHasKey('disabled', $view['save']->vars['attr']);
        self::assertSame('disabled', $view['save']->vars['attr']['disabled']);

        self::assertArrayHasKey('disabled', $view['saveAndContinue']->vars['attr']);
        self::assertSame('disabled', $view['saveAndContinue']->vars['attr']['disabled']);
    }

    public function testExistingAttributesArePreserved(): void
    {
        $factory = $this->createFormFactory(turnstileEnabled: true);

        $form = $factory->createBuilder(FormType::class)
            ->add('email', TextType::class)
            ->add('captcha', TurnstileType::class)
            ->add('submit', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary', 'data-loading' => 'true'],
            ])
            ->getForm();

        $view = $form->createView();

        self::assertSame('btn btn-primary', $view['submit']->vars['attr']['class']);
        self::assertSame('true', $view['submit']->vars['attr']['data-loading']);
        self::assertSame('disabled', $view['submit']->vars['attr']['disabled']);
    }

    private function createFormFactory(bool $turnstileEnabled): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addType(new TurnstileType('test-site-key', $turnstileEnabled))
            ->addTypeExtension(new TurnstileSubmitExtension($turnstileEnabled))
            ->getFormFactory();
    }
}
