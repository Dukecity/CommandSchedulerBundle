<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Command;

use Doctrine\Persistence\Mapping\MappingException;
use Dukecity\CommandSchedulerBundle\Command\UnlockCommand;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Fixtures\ORM\LoadScheduledCommandData;

/**
 * Class UnlockCommandTest.
 */
class UnlockCommandTest extends AbstractCommandTest
{
    /**
     * Test scheduler:unlock without --all option.
     */
    public function testUnlockAll(): void
    {
        // DataFixtures create 4 records
        $this->loadScheduledCommandFixtures();

        // One command is locked in fixture (2), another have a -1 return code as lastReturn (4)
        $output = $this->executeCommand(UnlockCommand::class, ['--all' => true])->getDisplay();

        $this->assertStringContainsString('CommandTestTwo', $output);
        $this->assertStringNotContainsString('CommandTestOne', $output);
        $this->assertStringNotContainsString('CommandTestThree', $output);

        try {
            $this->em->clear();
        } catch (MappingException $e) {
            echo 'Error with Mapping '.$e->getMessage();
        }
        $two = $this->em->getRepository(ScheduledCommand::class)->findOneBy(['name' => 'CommandTestTwo']);

        $this->assertFalse($two->isLocked());
    }

    /**
     * Test scheduler:unlock with given command name.
     */
    public function testUnlockByName(): void
    {
        // DataFixtures create 4 records
        $this->loadScheduledCommandFixtures();

        // One command is locked in fixture (2), another have a -1 return code as lastReturn (4)
        $output = $this->executeCommand(UnlockCommand::class, ['name' => 'CommandTestTwo'])->getDisplay();

        $this->assertStringContainsString('CommandTestTwo', $output);

        try {
            $this->em->clear();
        } catch (MappingException $e) {
            echo 'Error with Mapping '.$e->getMessage();
        }
        $two = $this->em->getRepository(ScheduledCommand::class)->findOneBy(['name' => 'CommandTestTwo']);

        $this->assertFalse($two->isLocked());
    }

    /**
     * Test scheduler:unlock with given command name and timeout.
     */
    public function testUnlockByNameWithTimout(): void
    {
        // DataFixtures create 4 records
        $this->loadScheduledCommandFixtures();

        // One command is locked in fixture with last execution two days ago (2),
        // another have a -1 return code as lastReturn (4)
        $output = $this->executeCommand(
            UnlockCommand::class,
            ['name' => 'CommandTestTwo', '--lock-timeout' => 3 * 24 * 60 * 60]
        )
            ->getDisplay();

        $this->assertStringContainsString('Skipping', $output);
        $this->assertStringContainsString('CommandTestTwo', $output);

        try {
            $this->em->clear();
        } catch (MappingException $e) {
            echo 'Error with Mapping '.$e->getMessage();
        }
        $two = $this->em->getRepository(ScheduledCommand::class)->findOneBy(['name' => 'CommandTestTwo']);

        $this->assertTrue($two->isLocked());
    }
}
