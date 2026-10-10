<?php

declare(strict_types=1);

namespace VuillaumeAgency\TurnstileBundle\Tests\Functional\App;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Security\Core\User\InMemoryUser;
use VuillaumeAgency\TurnstileBundle\Http\TurnstileHttpClientInterface;
use VuillaumeAgency\TurnstileBundle\VuillaumeAgencyTurnstileBundle;

/**
 * The smallest application that exercises protect_password_login through Symfony's real
 * AuthenticatorManager: a form_login firewall, a custom login-form authenticator, an http_basic
 * firewall that must stay untouched, and the Turnstile client replaced by a stub so nothing
 * reaches Cloudflare.
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new SecurityBundle();
        yield new TwigBundle();
        yield new VuillaumeAgencyTurnstileBundle();
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/vuillaume_agency_turnstile/'.md5(__DIR__.BaseKernel::VERSION).'/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/vuillaume_agency_turnstile/'.md5(__DIR__.BaseKernel::VERSION).'/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            // Explicit: framework-bundle 7.3+ deprecates relying on the default, and the suite
            // fails on any deprecation.
            'property_info' => ['with_constructor_extractor' => true],
            'session' => [
                'storage_factory_id' => 'session.storage.factory.mock_file',
                'handler_id' => null,
                'cookie_secure' => 'auto',
                'cookie_samesite' => 'lax',
            ],
        ]);

        $container->extension('twig', [
            'default_path' => __DIR__.'/templates',
        ]);

        $container->extension('security', [
            'password_hashers' => [InMemoryUser::class => 'plaintext'],
            'providers' => [
                'users' => ['memory' => ['users' => ['alice' => ['password' => 'secret', 'roles' => ['ROLE_USER']]]]],
            ],
            'firewalls' => [
                'api' => [
                    'pattern' => '^/api',
                    'stateless' => true,
                    'provider' => 'users',
                    'http_basic' => ['realm' => 'api'],
                ],
                'custom' => [
                    'pattern' => '^/custom',
                    'provider' => 'users',
                    'custom_authenticators' => [CustomLoginAuthenticator::class],
                ],
                'main' => [
                    'lazy' => true,
                    'provider' => 'users',
                    'form_login' => [
                        'login_path' => 'login',
                        'check_path' => 'login',
                        'enable_csrf' => false,
                        'default_target_path' => 'secured',
                        'always_use_default_target_path' => true,
                    ],
                ],
            ],
            'access_control' => [
                ['path' => '^/login', 'roles' => 'PUBLIC_ACCESS'],
                ['path' => '^/custom/login', 'roles' => 'PUBLIC_ACCESS'],
                ['path' => '^/', 'roles' => 'ROLE_USER'],
            ],
        ]);

        $container->extension('vuillaume_agency_turnstile', [
            'key' => 'site-key',
            'secret' => 'secret-key',
            'enable' => true,
            'protect_password_login' => true,
        ]);

        $services = $container->services();
        // The application overrides the bundle's alias: no test ever reaches Cloudflare.
        $services->set(TurnstileHttpClientInterface::class, TurnstileHttpClientStub::class)->public();
        // The kernel's default logger writes every event to stderr in debug mode: keep the test output clean.
        $services->set('logger', NullLogger::class);
        $services->set(CustomLoginAuthenticator::class)->autowire()->autoconfigure();
        $services->set(Controller::class)->autowire()->public()->tag('controller.service_arguments');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('login', '/login')->controller([Controller::class, 'login'])->methods(['GET', 'POST']);
        $routes->add('secured', '/secured')->controller([Controller::class, 'secured']);
        $routes->add('custom_login', '/custom/login')->controller([Controller::class, 'login'])->methods(['GET', 'POST']);
        $routes->add('custom_secured', '/custom/secured')->controller([Controller::class, 'secured']);
        $routes->add('api_ping', '/api/ping')->controller([Controller::class, 'secured']);
    }
}
