<?php

namespace App\Tests\Command;

use Doctrine\Persistence\Mapping\MappingException;
use Dukecity\CommandSchedulerBundle\Command\DisableCommand;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Tests\Command\AbstractCommandTest;

/**
 * Class DisableCommandTest.
 */
class DisableCommandTest extends AbstractCommandTest
{
    /**
     * Test scheduler:disable without --all option.
     */
    public function tesDisableAll(): void
    {
        // DataFixtures create 4 records
        $this->loadScheduledCommandFixtures();

        // One command is enabled
        $output = $this->executeCommand(DisableCommand::class, ['--all' => true])->getDisplay();

        $this->assertStringContainsString('CommandTestTwo', $output);
        $this->assertStringNotContainsString('CommandTestOne', $output);
        $this->assertStringNotContainsString('CommandTestThree', $output);

        try {
            $this->em->clear();
        } catch (MappingException $e) {
            echo 'Error with Mapping '.$e->getMessage();
        }
        $two = $this->em->getRepository(ScheduledCommand::class)->findOneBy(['name' => 'CommandTestTwo']);

        $this->assertFalse($two->isDisabled());
    }

    /**
     * Test scheduler:disable with given command name.
     */
    public function testDisableByName(): void
    {
        // DataFixtures create 4 records
        $this->loadScheduledCommandFixtures();

        $output = $this->executeCommand(DisableCommand::class, ['name' => 'CommandTestFive'])->getDisplay();

        $this->assertStringContainsString('CommandTestFive', $output);

        try {
            $this->em->clear();
        } catch (MappingException $e) {
            echo 'Error with Mapping '.$e->getMessage();
        }
        $two = $this->em->getRepository(ScheduledCommand::class)->findOneBy(['name' => 'CommandTestFive']);

        $this->assertTrue($two->isDisabled());
    }
}
