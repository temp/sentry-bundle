<?php

declare(strict_types=1);

namespace Temp\SentryBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Temp sentry configuration
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('temp_sentry');

        $builder
            ->getRootNode()
            ->children()
                ->scalarNode('dsn')
                    ->isRequired()
                ->end()
                ->scalarNode('environment')
                    ->isRequired()
                ->end()
                ->scalarNode('project_dir')
                    ->isRequired()
                ->end()
                ->scalarNode('cache_dir')
                    ->isRequired()
                ->end()
                ->scalarNode('source_dir')
                    ->isRequired()
                ->end()
                ->scalarNode('vendor_dir')
                    ->isRequired()
                ->end()
                ->scalarNode('app_version')
                    ->isRequired()
                ->end()
                ->scalarNode('console_listener')
                    ->defaultFalse()
                ->end()
                ->scalarNode('request_listener')
                    ->defaultFalse()
                ->end()
                ->scalarNode('user_listener')
                    ->defaultFalse()
                ->end()
                ->scalarNode('messenger_resetter')
                    ->defaultFalse()
                ->end()
            ->end()
            ->validate()
                ->ifTrue(static function ($v) {
                    return !empty($v['user_listener']) && empty($v['request_listener']);
                })
                ->thenInvalid('user_listener can only be enabled if request_listener is enabled.')
            ->end();

        return $builder;
    }
}
