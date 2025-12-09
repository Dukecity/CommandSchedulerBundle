<?php

namespace App\Tests\DependencyInjection\Compiler;

use Dukecity\CommandSchedulerBundle\DependencyInjection\Compiler\ScheduledCommandMappingPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ScheduledCommandMappingPassTest extends TestCase
{
    public function testDoesNothingWhenParameterNotSet(): void
    {
        $container = new ContainerBuilder();

        // Create a mock metadata driver definition with the bundle's entity namespace
        $driverDefinition = new Definition('Doctrine\Persistence\Mapping\Driver\MappingDriverChain');
        $driverDefinition->addMethodCall('addDriver', [
            new Definition('Doctrine\ORM\Mapping\Driver\AttributeDriver'),
            'Dukecity\\CommandSchedulerBundle\\Entity',
        ]);
        $container->setDefinition('doctrine.orm.default_metadata_driver', $driverDefinition);

        $pass = new ScheduledCommandMappingPass();
        $pass->process($container);

        // Driver should still be registered (parameter not set)
        $methodCalls = $container->getDefinition('doctrine.orm.default_metadata_driver')->getMethodCalls();
        $this->assertCount(1, $methodCalls);
    }

    public function testDoesNothingWhenUsingDefaultEntity(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('dukecity_command_scheduler.use_default_entity', true);

        // Create a mock metadata driver definition with the bundle's entity namespace
        $driverDefinition = new Definition('Doctrine\Persistence\Mapping\Driver\MappingDriverChain');
        $driverDefinition->addMethodCall('addDriver', [
            new Definition('Doctrine\ORM\Mapping\Driver\AttributeDriver'),
            'Dukecity\\CommandSchedulerBundle\\Entity',
        ]);
        $driverDefinition->addMethodCall('addDriver', [
            new Definition('Doctrine\ORM\Mapping\Driver\AttributeDriver'),
            'App\\Entity',
        ]);
        $container->setDefinition('doctrine.orm.default_metadata_driver', $driverDefinition);

        $pass = new ScheduledCommandMappingPass();
        $pass->process($container);

        // Both drivers should still be registered
        $methodCalls = $container->getDefinition('doctrine.orm.default_metadata_driver')->getMethodCalls();
        $this->assertCount(2, $methodCalls);

        $namespaces = array_map(fn($call) => $call[1][1], $methodCalls);
        $this->assertContains('Dukecity\\CommandSchedulerBundle\\Entity', $namespaces);
        $this->assertContains('App\\Entity', $namespaces);
    }

    public function testRemovesBundleEntityMappingWhenCustomClassIsConfigured(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('dukecity_command_scheduler.use_default_entity', false);

        // Create a mock metadata driver definition with the bundle's entity namespace
        $driverDefinition = new Definition('Doctrine\Persistence\Mapping\Driver\MappingDriverChain');
        $driverDefinition->addMethodCall('addDriver', [
            new Definition('Doctrine\ORM\Mapping\Driver\AttributeDriver'),
            'Dukecity\\CommandSchedulerBundle\\Entity',
        ]);
        $driverDefinition->addMethodCall('addDriver', [
            new Definition('Doctrine\ORM\Mapping\Driver\AttributeDriver'),
            'App\\Entity',
        ]);
        $container->setDefinition('doctrine.orm.default_metadata_driver', $driverDefinition);

        $pass = new ScheduledCommandMappingPass();
        $pass->process($container);

        // Only App\Entity should remain
        $methodCalls = $container->getDefinition('doctrine.orm.default_metadata_driver')->getMethodCalls();
        $this->assertCount(1, $methodCalls);

        $namespaces = array_map(fn($call) => $call[1][1], $methodCalls);
        $this->assertNotContains('Dukecity\\CommandSchedulerBundle\\Entity', $namespaces);
        $this->assertContains('App\\Entity', $namespaces);
    }

    public function testPreservesOtherNamespacesWhenRemovingBundleEntity(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('dukecity_command_scheduler.use_default_entity', false);

        $driverDefinition = new Definition('Doctrine\Persistence\Mapping\Driver\MappingDriverChain');
        $driverDefinition->addMethodCall('addDriver', [
            new Definition('Doctrine\ORM\Mapping\Driver\AttributeDriver'),
            'App\\Entity',
        ]);
        $driverDefinition->addMethodCall('addDriver', [
            new Definition('Doctrine\ORM\Mapping\Driver\AttributeDriver'),
            'Dukecity\\CommandSchedulerBundle\\Entity',
        ]);
        $driverDefinition->addMethodCall('addDriver', [
            new Definition('Doctrine\ORM\Mapping\Driver\AttributeDriver'),
            'AnotherBundle\\Entity',
        ]);
        $container->setDefinition('doctrine.orm.default_metadata_driver', $driverDefinition);

        $pass = new ScheduledCommandMappingPass();
        $pass->process($container);

        $methodCalls = $container->getDefinition('doctrine.orm.default_metadata_driver')->getMethodCalls();
        $this->assertCount(2, $methodCalls);

        $namespaces = array_map(fn($call) => $call[1][1], $methodCalls);
        $this->assertContains('App\\Entity', $namespaces);
        $this->assertContains('AnotherBundle\\Entity', $namespaces);
        $this->assertNotContains('Dukecity\\CommandSchedulerBundle\\Entity', $namespaces);
    }

    public function testHandlesMultipleMetadataDrivers(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('dukecity_command_scheduler.use_default_entity', false);

        // First driver (default)
        $driver1 = new Definition('Doctrine\Persistence\Mapping\Driver\MappingDriverChain');
        $driver1->addMethodCall('addDriver', [
            new Definition('Doctrine\ORM\Mapping\Driver\AttributeDriver'),
            'Dukecity\\CommandSchedulerBundle\\Entity',
        ]);
        $container->setDefinition('doctrine.orm.default_metadata_driver', $driver1);

        // Second driver (custom manager)
        $driver2 = new Definition('Doctrine\Persistence\Mapping\Driver\MappingDriverChain');
        $driver2->addMethodCall('addDriver', [
            new Definition('Doctrine\ORM\Mapping\Driver\AttributeDriver'),
            'Dukecity\\CommandSchedulerBundle\\Entity',
        ]);
        $driver2->addMethodCall('addDriver', [
            new Definition('Doctrine\ORM\Mapping\Driver\AttributeDriver'),
            'Other\\Entity',
        ]);
        $container->setDefinition('doctrine.orm.custom_metadata_driver', $driver2);

        $pass = new ScheduledCommandMappingPass();
        $pass->process($container);

        // Both drivers should have bundle namespace removed
        $calls1 = $container->getDefinition('doctrine.orm.default_metadata_driver')->getMethodCalls();
        $this->assertCount(0, $calls1);

        $calls2 = $container->getDefinition('doctrine.orm.custom_metadata_driver')->getMethodCalls();
        $this->assertCount(1, $calls2);
        $this->assertEquals('Other\\Entity', $calls2[0][1][1]);
    }

    public function testIgnoresNonDriverChainDefinitions(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('dukecity_command_scheduler.use_default_entity', false);

        // A definition that matches the naming pattern but isn't a MappingDriverChain
        $nonChainDriver = new Definition('SomeOtherDriver');
        $nonChainDriver->addMethodCall('addDriver', [
            new Definition('Doctrine\ORM\Mapping\Driver\AttributeDriver'),
            'Dukecity\\CommandSchedulerBundle\\Entity',
        ]);
        $container->setDefinition('doctrine.orm.default_metadata_driver', $nonChainDriver);

        $pass = new ScheduledCommandMappingPass();
        $pass->process($container);

        // Should remain unchanged (not a MappingDriverChain)
        $methodCalls = $container->getDefinition('doctrine.orm.default_metadata_driver')->getMethodCalls();
        $this->assertCount(1, $methodCalls);
    }
}
