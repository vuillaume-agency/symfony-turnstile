<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class VuillaumeAgencyTurnstileCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasParameter('twig.form.resources')) {
            /** @var array<int, string> $resources */
            $resources = $container->getParameter('twig.form.resources') ?: [];
            array_unshift($resources, '@VuillaumeAgencyTurnstile/fields.html.twig');
            $container->setParameter('twig.form.resources', $resources);
        }
    }
}
