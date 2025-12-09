<?php

namespace Dukecity\CommandSchedulerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass that removes the bundle's entity mapping from Doctrine's driver chain
 * when a custom entity class is configured.
 *
 * This prevents "table already exists" errors during migrations when users extend
 * BaseScheduledCommand with their own entity class.
 *
 * The pass runs after DoctrineOrmMappingsPass has added the mappings, and removes
 * them if use_default_entity is false.
 */
class ScheduledCommandMappingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Check if we should use the bundle's default entity
        if (!$container->hasParameter('dukecity_command_scheduler.use_default_entity')) {
            return;
        }

        $useDefaultEntity = $container->getParameter('dukecity_command_scheduler.use_default_entity');

        // If using default entity, keep the mapping
        if ($useDefaultEntity === true) {
            return;
        }

        // Custom entity class is configured - remove bundle's entity mapping
        $this->removeBundleEntityMapping($container);
    }

    private function removeBundleEntityMapping(ContainerBuilder $container): void
    {
        $bundleNamespace = 'Dukecity\\CommandSchedulerBundle\\Entity';

        // Find all Doctrine ORM metadata driver chain definitions
        foreach ($container->getDefinitions() as $id => $definition) {
            if (!str_starts_with($id, 'doctrine.orm.') || !str_ends_with($id, '_metadata_driver')) {
                continue;
            }

            $class = $definition->getClass();
            if ($class === null || !str_contains($class, 'MappingDriverChain')) {
                continue;
            }

            // Filter out addDriver calls for the bundle's entity namespace
            $methodCalls = $definition->getMethodCalls();
            $filteredCalls = [];

            foreach ($methodCalls as $methodCall) {
                [$method, $arguments] = $methodCall;

                // Keep all non-addDriver calls
                if ($method !== 'addDriver') {
                    $filteredCalls[] = $methodCall;
                    continue;
                }

                // For addDriver calls, check if it's the bundle's namespace
                $namespace = $arguments[1] ?? null;
                if ($namespace === $bundleNamespace) {
                    // Skip this call - don't add bundle's entity mapping
                    continue;
                }

                $filteredCalls[] = $methodCall;
            }

            $definition->setMethodCalls($filteredCalls);
        }
    }
}
