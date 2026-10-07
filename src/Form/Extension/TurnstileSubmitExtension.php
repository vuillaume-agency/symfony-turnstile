<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use VuillaumeAgency\TurnstileBundle\Type\TurnstileType;

/**
 * Automatically disables submit buttons when a Turnstile captcha field is present.
 * JavaScript will re-enable the button once the challenge is completed.
 *
 * Does nothing when the bundle is disabled: the widget and its script are not rendered then,
 * so nothing would ever re-enable the button.
 */
final class TurnstileSubmitExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly bool $enable,
        private readonly bool $disableSubmitUntilVerified,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [SubmitType::class];
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$this->enable || !$this->disableSubmitUntilVerified) {
            return;
        }

        $rootForm = $form->getRoot();

        if (!$this->formHasTurnstile($rootForm)) {
            return;
        }

        $view->vars['attr']['disabled'] = 'disabled';
    }

    private function formHasTurnstile(FormInterface $form): bool
    {
        foreach ($form->all() as $child) {
            if ($child->getConfig()->getType()->getInnerType() instanceof TurnstileType) {
                return true;
            }
        }

        return false;
    }
}
