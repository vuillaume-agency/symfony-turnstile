<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\DependencyInjection;

use LogicException;
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

        // The login listener exists in the container only when asked for: a project without a
        // password login, or one that verifies the token in its own code, carries nothing extra.
        if ($config['protect_password_login']) {
            if (!class_exists(CheckPassportEvent::class)) {
                throw new LogicException('The "vuillaume_agency_turnstile.protect_password_login" option needs the Symfony security component: run "composer require symfony/security-http" (symfony/security-bundle installs it).');
            }

            $loader->load('security.yml');
        }

        foreach ($config as $key => $value) {
            $container->setParameter('vuillaume_agency_turnstile.'.$key, $value);
        }
    }
}
