<?php

declare (strict_types=1);

namespace Temp\SentryBundleTests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Temp\SentryBundle\DependencyInjection\TempSentryExtension;
use Temp\SentryBundle\EventListener\MonologResetterEventListener;
use Temp\SentryBundle\EventListener\SentryConsoleListener;
use Temp\SentryBundle\EventListener\SentryRequestListener;
use Temp\SentryBundle\EventListener\SentryUserListener;

/**
 * @covers \Temp\SentryBundle\DependencyInjection\TempSentryExtension
 */
final class TempSentryExtensionTest extends TestCase
{
    public function testDsnIsRequired(): void
    {
        $this->expectExceptionMessage('The child node "dsn" at path "temp_sentry" must be configured.');

        $extension = new TempSentryExtension();
        $extension->load([], new ContainerBuilder());
    }

    public function testEnvironmentIsRequired(): void
    {
        $this->expectExceptionMessage('The child node "environment" at path "temp_sentry" must be configured.');

        $extension = new TempSentryExtension();
        $extension->load([['dsn' => 'foo']], new ContainerBuilder());
    }

    public function testProjectDirIsRequired(): void
    {
        $this->expectExceptionMessage('The child node "project_dir" at path "temp_sentry" must be configured.');

        $extension = new TempSentryExtension();
        $extension->load([['dsn' => 'foo', 'environment' => 'foo']], new ContainerBuilder());
    }

    public function testCacheDirIsRequired(): void
    {
        $this->expectExceptionMessage('The child node "cache_dir" at path "temp_sentry" must be configured.');

        $extension = new TempSentryExtension();
        $extension->load([['dsn' => 'foo', 'environment' => 'foo', 'project_dir' => 'foo']], new ContainerBuilder());
    }

    public function testSourceDirIsRequired(): void
    {
        $this->expectExceptionMessage('The child node "source_dir" at path "temp_sentry" must be configured.');

        $extension = new TempSentryExtension();
        $extension->load(
            [
                [
                    'dsn' => 'foo',
                    'environment' => 'foo',
                    'project_dir' => 'foo',
                    'cache_dir' => 'foo',
                ],
            ],
            new ContainerBuilder(),
        );
    }

    public function testVendorDirIsRequired(): void
    {
        $this->expectExceptionMessage('The child node "vendor_dir" at path "temp_sentry" must be configured.');

        $extension = new TempSentryExtension();
        $extension->load(
            [
                [
                    'dsn' => 'foo',
                    'environment' => 'foo',
                    'project_dir' => 'foo',
                    'cache_dir' => 'foo',
                    'source_dir' => 'foo',
                ],
            ],
            new ContainerBuilder(),
        );
    }

    public function testAppVersionIsRequired(): void
    {
        $this->expectExceptionMessage('The child node "app_version" at path "temp_sentry" must be configured.');

        $extension = new TempSentryExtension();
        $extension->load(
            [
                [
                    'dsn' => 'foo',
                    'environment' => 'foo',
                    'project_dir' => 'foo',
                    'cache_dir' => 'foo',
                    'source_dir' => 'foo',
                    'vendor_dir' => 'foo',
                ],
            ],
            new ContainerBuilder(),
        );
    }

    public function testListenersAreNotRegisteredByDefault(): void
    {
        $container = new ContainerBuilder();

        $extension = new TempSentryExtension();
        $extension->load(
            [
                [
                    'dsn' => 'foo',
                    'environment' => 'foo',
                    'project_dir' => 'foo',
                    'cache_dir' => 'foo',
                    'source_dir' => 'foo',
                    'vendor_dir' => 'foo',
                    'app_version' => 'foo',
                ],
            ],
            $container,
        );

        $this->assertFalse($container->has(SentryConsoleListener::class));
        $this->assertFalse($container->has(SentryRequestListener::class));
        $this->assertFalse($container->has(SentryUserListener::class));
        $this->assertFalse($container->has(MonologResetterEventListener::class));
    }

    public function testConsoleListenerIsRegistered(): void
    {
        $container = new ContainerBuilder();

        $extension = new TempSentryExtension();
        $extension->load(
            [
                [
                    'dsn' => 'foo',
                    'environment' => 'foo',
                    'project_dir' => 'foo',
                    'cache_dir' => 'foo',
                    'source_dir' => 'foo',
                    'vendor_dir' => 'foo',
                    'app_version' => 'foo',
                    'console_listener' => true,
                ],
            ],
            $container,
        );

        $this->assertTrue($container->has(SentryConsoleListener::class));
        $this->assertFalse($container->has(SentryRequestListener::class));
        $this->assertFalse($container->has(SentryUserListener::class));
        $this->assertFalse($container->has(MonologResetterEventListener::class));
    }

    public function testRequestListenerIsRegistered(): void
    {
        $container = new ContainerBuilder();

        $extension = new TempSentryExtension();
        $extension->load(
            [
                [
                    'dsn' => 'foo',
                    'environment' => 'foo',
                    'project_dir' => 'foo',
                    'cache_dir' => 'foo',
                    'source_dir' => 'foo',
                    'vendor_dir' => 'foo',
                    'app_version' => 'foo',
                    'request_listener' => true,
                ],
            ],
            $container,
        );

        $this->assertTrue($container->has(SentryRequestListener::class));
        $this->assertFalse($container->has(SentryConsoleListener::class));
        $this->assertFalse($container->has(SentryUserListener::class));
        $this->assertFalse($container->has(MonologResetterEventListener::class));
    }

    public function testUserListenerIsRegistered(): void
    {
        $container = new ContainerBuilder();

        $extension = new TempSentryExtension();
        $extension->load(
            [
                [
                    'dsn' => 'foo',
                    'environment' => 'foo',
                    'project_dir' => 'foo',
                    'cache_dir' => 'foo',
                    'source_dir' => 'foo',
                    'vendor_dir' => 'foo',
                    'app_version' => 'foo',
                    'request_listener' => true,
                    'user_listener' => true,
                ],
            ],
            $container,
        );

        $this->assertTrue($container->has(SentryUserListener::class));
        $this->assertTrue($container->has(SentryRequestListener::class));
        $this->assertFalse($container->has(SentryConsoleListener::class));
        $this->assertFalse($container->has(MonologResetterEventListener::class));
    }

    public function testMessengerResetterIsRegistered(): void
    {
        $container = new ContainerBuilder();

        $extension = new TempSentryExtension();
        $extension->load(
            [
                [
                    'dsn' => 'foo',
                    'environment' => 'foo',
                    'project_dir' => 'foo',
                    'cache_dir' => 'foo',
                    'source_dir' => 'foo',
                    'vendor_dir' => 'foo',
                    'app_version' => 'foo',
                    'messenger_resetter' => true,
                ],
            ],
            $container,
        );

        $this->assertTrue($container->has(MonologResetterEventListener::class));
        $this->assertFalse($container->has(SentryConsoleListener::class));
        $this->assertFalse($container->has(SentryRequestListener::class));
        $this->assertFalse($container->has(SentryUserListener::class));
    }
}
