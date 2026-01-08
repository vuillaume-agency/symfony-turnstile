<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VuillaumeAgency\TurnstileBundle\Validator\CloudflareTurnstile;

class TurnstileType extends AbstractType
{
    public function __construct(
        private readonly string $key,
        private readonly bool $enable,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'missing_response_message' => null,
            'verification_failed_message' => null,
        ]);

        $resolver->setAllowedTypes('missing_response_message', ['null', 'string']);
        $resolver->setAllowedTypes('verification_failed_message', ['null', 'string']);

        $resolver->setNormalizer('constraints', function ($resolver, $value) {
            $options = [];

            if (null !== $resolver['missing_response_message']) {
                $options['missingResponseMessage'] = $resolver['missing_response_message'];
            }

            if (null !== $resolver['verification_failed_message']) {
                $options['verificationFailedMessage'] = $resolver['verification_failed_message'];
            }

            return [new CloudflareTurnstile(...$options)];
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['key'] = $this->key;
        $view->vars['enable'] = $this->enable;
    }

    public function getBlockPrefix(): string
    {
        return 'turnstile';
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }
}
