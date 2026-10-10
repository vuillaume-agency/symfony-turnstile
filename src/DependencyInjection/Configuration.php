<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('vuillaume_agency_turnstile');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->scalarNode('enable')
            ->defaultTrue()
            ->end()
            ->scalarNode('key')
            ->defaultValue('%env(TURNSTILE_KEY)%')
            ->end()
            ->scalarNode('secret')
            ->defaultValue('%env(TURNSTILE_SECRET)%')
            ->end()
            ->booleanNode('disable_submit_until_verified')
            ->defaultFalse()
            ->end()
            // Symfony's form_login handles the login POST itself, outside any form type, so a
            // TurnstileType field cannot cover the login page. This option verifies the token
            // during authentication instead (form_login and custom login-form authenticators).
            // Off by default in 1.x so that an existing installation keeps its behaviour on
            // upgrade; new installations get it from the recipe. Requires symfony/security-http.
            ->booleanNode('protect_password_login')
            ->defaultFalse()
            ->end()
            // Shown when the login token is missing or refused. The English sentence is the
            // translation key, translated through the "security" domain like Symfony's own
            // "Invalid credentials." (rendered by error.messageKey|trans(error.messageData, 'security')).
            ->scalarNode('password_login_message')
            ->defaultValue('The security check failed. Please try again.')
            ->cannotBeEmpty()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
