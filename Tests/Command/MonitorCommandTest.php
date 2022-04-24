<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Command;

use Dukecity\CommandSchedulerBundle\Command\MonitorCommand;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Fixtures\ORM\LoadScheduledCommandData;

/**
 * Class MonitorCommandTest.
 */
class MonitorCommandTest extends AbstractCommandTest
{
    /**
     * Test scheduler:execute without option.
     */
    public function testExecuteWithError(): void
    {
        // DataFixtures create 4 records
        $this->loadScheduledCommandFixtures();

        $output = $this->executeCommand(MonitorCommand::class, ['--dump' => true])->getDisplay();

        // One command is locked in fixture (2), another have a -1 return code as lastReturn (4)
        $this->assertStringContainsString('CommandTestTwo', $output);
        $this->assertStringContainsString('CommandTestFour', $output);
    }

    /**
     * Test scheduler:execute without option.
     */
    public function testExecuteWithoutError(): void
    {
        // DataFixtures create 4 records
        $this->loadScheduledCommandFixtures();

        $two = $this->em->getRepository(ScheduledCommand::class)->find(2);
        $four = $this->em->getRepository(ScheduledCommand::class)->find(4);
        $two->setLocked(false);
        $four->setLastReturnCode(0);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->em->flush();

        // None command should be in error status here.

        // One command is locked in fixture (2), another have a -1 return code as lastReturn (4)
        $output = $this->executeCommand(MonitorCommand::class, ['--dump' => true])->getDisplay();

        $this->assertStringStartsWith('No errors found.', $output);
    }
}
