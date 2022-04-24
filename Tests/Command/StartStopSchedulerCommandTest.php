<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Command;

use Dukecity\CommandSchedulerBundle\Command\StartSchedulerCommand;
use Dukecity\CommandSchedulerBundle\Command\StopSchedulerCommand;

class StartStopSchedulerCommandTest extends AbstractCommandTest
{
    /**
     * Test scheduler:start and scheduler:stop.
     */
    public function testStartAndStopScheduler(): void
    {
        // DataFixtures create 4 records
        $this->loadScheduledCommandFixtures();

        $pidFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.StartSchedulerCommand::PID_FILE;

        $output = $this->executeCommand(StartSchedulerCommand::class)->getDisplay();
        $this->assertStringStartsWith('Command scheduler started in non-blocking mode...', $output);
        $this->assertFileExists($pidFile);

        $output = $this->executeCommand(StopSchedulerCommand::class)->getDisplay();
        $this->assertStringStartsWith('Command scheduler is stopped.', $output);

        $this->assertFileDoesNotExist($pidFile);
    }
}
