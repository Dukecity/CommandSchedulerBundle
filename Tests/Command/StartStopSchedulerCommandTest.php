<?php

namespace Dukecity\CommandSchedulerBundle\Tests\Command;

use Dukecity\CommandSchedulerBundle\Command\StartSchedulerCommand;
use Dukecity\CommandSchedulerBundle\Command\StopSchedulerCommand;
use Symfony\Component\Process\Process;

class StartStopSchedulerCommandTest extends AbstractCommandTest
{
    private string $pidFile;

    public function setUp(): void
    {
        parent::setUp();

        $this->pidFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.StartSchedulerCommand::PID_FILE;
    }

    protected function tearDown(): void
    {
        // never leave a forked scheduler behind
        if (file_exists($this->pidFile)) {
            posix_kill((int) file_get_contents($this->pidFile), SIGKILL);
            unlink($this->pidFile);
        }

        parent::tearDown();
    }

    /**
     * Test scheduler:start and scheduler:stop.
     */
    public function testStartAndStopScheduler(): void
    {
        if (!extension_loaded('pcntl')) {
            self::markTestSkipped('The pcntl extension is required.');
        }

        // DataFixtures create 4 records
        $this->loadScheduledCommandFixtures();

        // scheduler:start forks, so it must run in its own process and not inside PHPUnit
        $start = $this->createConsoleProcess('scheduler:start');
        $start->mustRun();
        $this->assertStringStartsWith('Command scheduler started in non-blocking mode...', $start->getOutput());
        $this->assertFileExists($this->pidFile);

        $pid = (int) file_get_contents($this->pidFile);
        $this->assertTrue(posix_kill($pid, 0), 'Scheduler process is not running.');

        $output = $this->executeCommand(StopSchedulerCommand::class)->getDisplay();
        $this->assertStringStartsWith('Command scheduler is stopped.', $output);
        $this->assertFileDoesNotExist($this->pidFile);
    }

    private function createConsoleProcess(string $command): Process
    {
        $code = sprintf(
            'require %s; exit((new %s(new %s()))->run(new %s(["console", %s])));',
            var_export(dirname(__DIR__, 2).'/vendor/autoload.php', true),
            \Symfony\Bundle\FrameworkBundle\Console\Application::class,
            \App\Tests\App\AppKernel::class,
            \Symfony\Component\Console\Input\ArgvInput::class,
            var_export($command, true),
        );

        $process = new Process([PHP_BINARY, '-r', $code]);
        $process->setTimeout(30);

        return $process;
    }
}
