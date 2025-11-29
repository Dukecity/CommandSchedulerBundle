<?php

namespace Dukecity\CommandSchedulerBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Dukecity\CommandSchedulerBundle\DependencyInjection\Compiler\ScheduledCommandMappingPass;
use Dukecity\CommandSchedulerBundle\DependencyInjection\DukecityCommandSchedulerExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;

class DukecityCommandSchedulerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $ormCompilerClass = DoctrineOrmMappingsPass::class;

        if (class_exists($ormCompilerClass))
        {
            $namespaces = ['Dukecity\CommandSchedulerBundle\Entity'];
            $directories = [realpath(__DIR__.'/Entity')];
            $managerParameters = [];
            $enabledParameter = false;

            $driver = new Definition(AttributeDriver::class, [$directories]);

            $container->addCompilerPass(
                new DoctrineOrmMappingsPass(
                    $driver,
                    $namespaces,
                    $managerParameters,
                    $enabledParameter
                )
            );

            // Register pass to remove bundle's entity mapping when custom class is configured.
            // Must run after DoctrineOrmMappingsPass has added the mappings.
            // Priority -10 ensures it runs after DoctrineOrmMappingsPass (priority 0).
            $container->addCompilerPass(
                new ScheduledCommandMappingPass(),
                PassConfig::TYPE_BEFORE_OPTIMIZATION,
                -10
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): DukecityCommandSchedulerExtension
    {
        $class = $this->getContainerExtensionClass();

        return new $class();
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerExtensionClass(): string
    {
        return DukecityCommandSchedulerExtension::class;
    }
}
