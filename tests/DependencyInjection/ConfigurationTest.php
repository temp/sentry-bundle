<?php

declare (strict_types=1);

namespace Temp\SentryBundleTests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Temp\SentryBundle\DependencyInjection\Configuration;

/**
 * @covers \Temp\SentryBundle\DependencyInjection\Configuration
 */
final class ConfigurationTest extends TestCase
{
    public function testConfigurationHasCorrectName(): void
    {
        $configuration = new Configuration();

        $this->assertSame('temp_sentry', $configuration->getConfigTreeBuilder()->buildTree()->getName());
    }

    public function testUserOnlyIfRequestIsEnabled(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $this->expectExceptionMessage('Invalid configuration for path "temp_sentry": user_listener can only be enabled if request_listener is enabled.');

        $configuration = new Configuration();

        $processor = new Processor();
        $processor->process($configuration->getConfigTreeBuilder()->buildTree(), [
            'temp_sentry' => [
                'dsn' => 'foo',
                'environment' => 'foo',
                'cache_dir' => 'foo',
                'project_dir' => 'foo',
                'source_dir' => 'foo',
                'vendor_dir' => 'foo',
                'app_version' => 'foo',
                'user_listener' => true,
            ],
        ]);
    }
}
