<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Service;

use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommandInterface;
use Dukecity\CommandSchedulerBundle\Service\ScheduledCommandFactory;
use Dukecity\CommandSchedulerBundle\Tests\Entity\TestCustomScheduledCommand;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests for ScheduledCommandFactory service.
 */
class ScheduledCommandFactoryTest extends WebTestCase
{
    private ScheduledCommandFactory $factory;

    protected function setUp(): void
    {
        $client = self::createClient();
        $container = $client->getContainer()->get('test.service_container');
        $this->factory = $container->get(ScheduledCommandFactory::class);
    }

    public function testFactoryIsRegisteredAsService(): void
    {
        $this->assertInstanceOf(ScheduledCommandFactory::class, $this->factory);
    }

    public function testCreateReturnsScheduledCommandInterface(): void
    {
        $entity = $this->factory->create();

        $this->assertInstanceOf(ScheduledCommandInterface::class, $entity);
    }

    public function testCreateReturnsDefaultClass(): void
    {
        $entity = $this->factory->create();

        // With default configuration, should return ScheduledCommand instance
        $this->assertInstanceOf(ScheduledCommand::class, $entity);
    }

    public function testGetEntityClassReturnsConfiguredClass(): void
    {
        $entityClass = $this->factory->getEntityClass();

        $this->assertSame(ScheduledCommand::class, $entityClass);
    }

    public function testCreateReturnsNewInstanceEachTime(): void
    {
        $entity1 = $this->factory->create();
        $entity2 = $this->factory->create();

        $this->assertNotSame($entity1, $entity2);
    }

    public function testFactoryWithCustomClass(): void
    {
        // Create a factory with custom entity class directly
        $customFactory = new ScheduledCommandFactory(TestCustomScheduledCommand::class);

        $entity = $customFactory->create();

        $this->assertInstanceOf(ScheduledCommandInterface::class, $entity);
        $this->assertInstanceOf(TestCustomScheduledCommand::class, $entity);
        $this->assertSame(TestCustomScheduledCommand::class, $customFactory->getEntityClass());
    }

    public function testCreatedEntityIsFullyFunctional(): void
    {
        $entity = $this->factory->create();

        // Verify we can use all the interface methods
        $entity->setName('factory-test');
        $entity->setCommand('cache:clear');
        $entity->setCronExpression('@daily');
        $entity->setPriority(5);

        $this->assertSame('factory-test', $entity->getName());
        $this->assertSame('cache:clear', $entity->getCommand());
        $this->assertSame('@daily', $entity->getCronExpression());
        $this->assertSame(5, $entity->getPriority());
    }
}
