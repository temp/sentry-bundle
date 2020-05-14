<?php

declare(strict_types=1);

namespace Temp\SentryBundle\DependencyInjection;

use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Security\Core\Security;
use function array_key_exists;
use function assert;
use function class_exists;
use function interface_exists;
use const PHP_OS;
use const PHP_SAPI;
use const PHP_VERSION;

// phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed

/**
 * Temp sentry extension.
 */
final class TempSentryExtension extends Extension
{
    /**
     * @param mixed[] $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $configuration = $this->getConfiguration($configs, $container);
        assert($configuration instanceof Configuration);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sentry.cache_dir', $config['cache_dir']);
        $container->setParameter('sentry.project_dir', $config['project_dir']);
        $container->setParameter('sentry.source_dir', $config['source_dir']);
        $container->setParameter('sentry.vendor_dir', $config['vendor_dir']);
        $container->setParameter('sentry.dsn', $config['dsn']);
        $container->setParameter('sentry.environment', $config['environment']);
        $container->setParameter('sentry.tags.app_version', $config['app_version']);
        $container->setParameter('sentry.tags.php_uname', PHP_OS);
        $container->setParameter('sentry.tags.php_sapi', PHP_SAPI);
        $container->setParameter('sentry.tags.php_version', PHP_VERSION);
        $container->setParameter('sentry.tags.framework', 'symfony');
        $container->setParameter('sentry.tags.symfony_version', Kernel::VERSION);
        $container->setParameter('sentry.tags.symfony_environment', '%kernel.environment%');

        if (array_key_exists('console_listener', $config) && $config['console_listener']) {
            if (!class_exists(ConsoleEvents::class)) {
                throw new LogicException('symfony/console has to be installed for the console_listener');
            }

            if (!interface_exists(LoggerInterface::class)) {
                throw new LogicException('psr/log has to be installed for the console_listener');
            }

            $loader->load('console.yml');
        }

        if (array_key_exists('request_listener', $config) && $config['request_listener']) {
            if (!interface_exists(LoggerInterface::class)) {
                throw new LogicException('psr/log has to be installed for the request_listener');
            }

            $loader->load('request.yml');
        }

        if (array_key_exists('user_listener', $config) && $config['user_listener']) {
            if (!class_exists(Security::class)) {
                throw new LogicException('symfony/security-core has to be installed for the user_listener');
            }

            $loader->load('user.yml');
        }

        if (array_key_exists('messenger_resetter', $config) && $config['messenger_resetter']) {
            if (!class_exists(WorkerMessageHandledEvent::class)) {
                throw new LogicException('symfony/messenger has to be installed for the messenger_resetter');
            }

            $loader->load('messenger.yml');
        }
    }
}
