<?php

namespace Dukecity\CommandSchedulerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dukecity_command_scheduler');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('doctrine_manager')->defaultValue('default')->end()
                ->scalarNode('log_path')->defaultValue('%kernel.logs_dir%')->end()
                ->scalarNode('lock_timeout')->defaultValue(false)->end()
                ->arrayNode('monitor_mail')
                    ->defaultValue([])
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('monitor_mail_subject')->defaultValue('cronjob monitoring %%s, %%s')->end()
                ->booleanNode('send_ok')->defaultValue(false)->end()
                ->variableNode('excluded_command_namespaces')
                    ->defaultValue([])
                    ->validate()
                        ->always(function ($value) {
                            if (null === $value) {
                                return [];
                            }

                            if (is_string($value)) {
                                return explode(',', $value);
                            }

                            return $value;
                        })
                    ->end()
                ->end()
                ->variableNode('included_command_namespaces')
                    ->defaultValue([])
                    ->validate()
                        ->always(function ($value) {
                            if (null === $value) {
                                return [];
                            }

                            if (is_string($value)) {
                                return explode(',', $value);
                            }

                            return $value;
                        })
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
