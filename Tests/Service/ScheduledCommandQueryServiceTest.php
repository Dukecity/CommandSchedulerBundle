<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommandInterface;
use Dukecity\CommandSchedulerBundle\Service\ScheduledCommandFactory;
use Dukecity\CommandSchedulerBundle\Service\ScheduledCommandQueryService;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests for ScheduledCommandQueryService.
 */
class ScheduledCommandQueryServiceTest extends WebTestCase
{
    private ScheduledCommandQueryService $queryService;
    private ScheduledCommandFactory $factory;
    private EntityManagerInterface $em;
    private AbstractDatabaseTool $databaseTool;
    private static bool $schemaCreated = false;

    protected function setUp(): void
    {
        $client = self::createClient();
        $container = $client->getContainer()->get('test.service_container');
        $this->queryService = $container->get(ScheduledCommandQueryService::class);
        $this->factory = $container->get(ScheduledCommandFactory::class);
        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();

        // Create schema on first run
        if (!self::$schemaCreated) {
            $schemaTool = new SchemaTool($this->em);
            $metadata = $this->em->getMetadataFactory()->getAllMetadata();
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
            self::$schemaCreated = true;
        }

        // Load fixtures
        $this->databaseTool->loadFixtures([]);
    }

    public function testServiceIsRegistered(): void
    {
        $this->assertInstanceOf(ScheduledCommandQueryService::class, $this->queryService);
    }

    public function testFindEnabledCommandReturnsArray(): void
    {
        $result = $this->queryService->findEnabledCommand();

        $this->assertIsArray($result);
    }

    public function testFindLockedCommandReturnsArray(): void
    {
        $result = $this->queryService->findLockedCommand();

        $this->assertIsArray($result);
    }

    public function testFindFailedCommandReturnsArray(): void
    {
        $result = $this->queryService->findFailedCommand();

        $this->assertIsArray($result);
    }

    public function testFindCommandsToExecuteReturnsArray(): void
    {
        $result = $this->queryService->findCommandsToExecute();

        $this->assertIsArray($result);
    }

    public function testFindFailedAndTimeoutCommandsReturnsArray(): void
    {
        $result = $this->queryService->findFailedAndTimeoutCommands(3600);

        $this->assertIsArray($result);
    }

    public function testFindEnabledCommandExcludesDisabled(): void
    {
        // Create a disabled command
        $command = $this->factory->create();
        $command->setName('test-disabled-' . uniqid('', true));
        $command->setCommand('cache:clear');
        $command->setCronExpression('@daily');
        $command->setDisabled(true);

        $this->em->persist($command);
        $this->em->flush();

        $result = $this->queryService->findEnabledCommand();

        $foundDisabled = false;
        foreach ($result as $cmd) {
            if ($cmd->getId() === $command->getId()) {
                $foundDisabled = true;
                break;
            }
        }

        $this->assertFalse($foundDisabled, 'Disabled command should not be in enabled list');
    }

    public function testFindEnabledCommandExcludesLocked(): void
    {
        // Create a locked command
        $command = $this->factory->create();
        $command->setName('test-locked-' . uniqid('', true));
        $command->setCommand('cache:clear');
        $command->setCronExpression('@daily');
        $command->setLocked(true);

        $this->em->persist($command);
        $this->em->flush();

        $result = $this->queryService->findEnabledCommand();

        $foundLocked = false;
        foreach ($result as $cmd) {
            if ($cmd->getId() === $command->getId()) {
                $foundLocked = true;
                break;
            }
        }

        $this->assertFalse($foundLocked, 'Locked command should not be in enabled list');
    }

    public function testFindLockedCommandFindsLockedCommands(): void
    {
        // Create a locked command
        $command = $this->factory->create();
        $command->setName('test-find-locked-' . uniqid('', true));
        $command->setCommand('cache:clear');
        $command->setCronExpression('@daily');
        $command->setLocked(true);
        $command->setDisabled(false);

        $this->em->persist($command);
        $this->em->flush();

        $result = $this->queryService->findLockedCommand();

        $foundLocked = false;
        foreach ($result as $cmd) {
            if ($cmd->getId() === $command->getId()) {
                $foundLocked = true;
                break;
            }
        }

        $this->assertTrue($foundLocked, 'Locked command should be found');
    }

    public function testFindFailedCommandFindsFailedCommands(): void
    {
        // Create a failed command
        $command = $this->factory->create();
        $command->setName('test-failed-' . uniqid('', true));
        $command->setCommand('cache:clear');
        $command->setCronExpression('@daily');
        $command->setLastReturnCode(1); // Non-zero = failed
        $command->setDisabled(false);

        $this->em->persist($command);
        $this->em->flush();

        $result = $this->queryService->findFailedCommand();

        $foundFailed = false;
        foreach ($result as $cmd) {
            if ($cmd->getId() === $command->getId()) {
                $foundFailed = true;
                break;
            }
        }

        $this->assertTrue($foundFailed, 'Failed command should be found');
    }

    public function testGetNotLockedCommandReturnsNullForLockedCommand(): void
    {
        // Create a locked command
        $command = $this->factory->create();
        $command->setName('test-not-locked-' . uniqid('', true));
        $command->setCommand('cache:clear');
        $command->setCronExpression('@daily');
        $command->setLocked(true);

        $this->em->persist($command);
        $this->em->flush();

        $this->em->beginTransaction();
        try {
            $result = $this->queryService->getNotLockedCommand($command);
            $this->assertNull($result, 'Locked command should return null');
        } finally {
            $this->em->rollback();
        }
    }

    public function testGetNotLockedCommandReturnsCommandForUnlockedCommand(): void
    {
        // Create an unlocked command
        $command = $this->factory->create();
        $command->setName('test-unlocked-' . uniqid('', true));
        $command->setCommand('cache:clear');
        $command->setCronExpression('@daily');
        $command->setLocked(false);

        $this->em->persist($command);
        $this->em->flush();

        $this->em->beginTransaction();
        try {
            $result = $this->queryService->getNotLockedCommand($command);
            $this->assertInstanceOf(ScheduledCommandInterface::class, $result);
            $this->assertSame($command->getId(), $result->getId());
        } finally {
            $this->em->rollback();
        }
    }
}
