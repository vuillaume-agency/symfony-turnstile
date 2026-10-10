<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class VuillaumeAgencyTurnstileExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // The login listener exists in the container only when asked for, and only when the
        // Symfony security component is installed. Without it there is no password login to
        // protect, so the option has nothing to do: the 1.2 recipe turns it on for every new
        // installation, and a project without security-bundle must still install cleanly.
        if ($config['protect_password_login'] && class_exists(CheckPassportEvent::class)) {
            $loader->load('security.yml');
        }

        foreach ($config as $key => $value) {
            $container->setParameter('vuillaume_agency_turnstile.'.$key, $value);
        }
    }
}
