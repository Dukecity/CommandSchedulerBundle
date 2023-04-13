<?php

namespace Dukecity\CommandSchedulerBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Dukecity\CommandSchedulerBundle\DependencyInjection\DukecityCommandSchedulerExtension;
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

                # TODO
            /** If this is merged it could be renamed https://github.com/doctrine/DoctrineBundle/pull/1369/files
             * new DoctrineOrmMappingsPass(
             * DoctrineOrmMappingsPass::createPhpMappingDriver(
             * $namespaces,
            $directories,
            $managerParameters,
            $enabledParameter,
            $aliasMap)
             */
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
