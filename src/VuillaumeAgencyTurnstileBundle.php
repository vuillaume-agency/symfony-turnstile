<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class VuillaumeAgencyTurnstileBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new VuillaumeAgencyTurnstileCompilerPass());
    }
}
