<?php /** @noinspection ALL */

namespace Dukecity\CommandSchedulerBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Code originally taken from https://github.com/Cron/Symfony-Bundle/blob/2.1.0/Command/CronStartCommand.php
 * License: MIT (according to https://github.com/Cron/Symfony-Bundle/blob/2.1.0/LICENSE)
 * Original author: Alexander Lokhman <alex.lokhman@gmail.com>.
 *
 * Adaption to CommandSchedulerBundle by Christoph Singer <singer@webagentur72.de>
 */
#[AsCommand(name: 'scheduler:start', description: 'Starts command scheduler')]
class StartSchedulerCommand extends Command
{
    const PID_FILE = '.cron-pid';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Starts command scheduler')
            ->addOption('blocking', 'b', InputOption::VALUE_NONE, 'Run in blocking mode.')
        ->setHelp(<<<'HELP'
The <info>%command.name%</info> is for running the manual command scheduler:

You can enable the blocking mode with
<info>php %command.full_name%</info> <comment>-b, --blocking</comment>

Deamon (Beta) : If you don't want to set up a cron job, you can use 
<comment>scheduler:start</comment> and <comment>scheduler:stop</comment> commands.
This commands manage a deamon process that will call scheduler:execute every minute. 
It require the pcntlphp extension.
Note that with this mode, if a command with an error, it will stop all the scheduler.

Note : Each command is locked just before his execution (and unlocked after). 
This system avoid to have simultaneous process for the same command. Thus, 
if an non-catchable error occurs, the command won't be executed again unless the problem 
is solved and the task is unlocked manually

HELP
    );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('blocking')) {
            $output->writeln(sprintf('<info>%s</info>', 'Starting command scheduler in blocking mode. Press CTRL+C to cancel'));
            $this->scheduler($output->isVerbose() ? $output : new NullOutput(), null);

            return Command::SUCCESS;
        }

        if (!extension_loaded('pcntl')) {
            throw new \RuntimeException('This command needs the pcntl extension to run.');
        }

        $pidFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.self::PID_FILE;

        if (-1 === $pid = pcntl_fork()) {
            throw new \RuntimeException('Unable to start the cron process.');
        } elseif (0 !== $pid) {
            if (false === file_put_contents($pidFile, $pid)) {
                throw new \RuntimeException('Unable to create process file.');
            }

            $output->writeln(sprintf('<info>%s</info>', 'Command scheduler started in non-blocking mode...'));

            return Command::SUCCESS;
        }

        if (-1 === posix_setsid()) {
            throw new \RuntimeException('Unable to set the child process as session leader.');
        }

        $this->scheduler(new NullOutput(), $pidFile);

        return Command::SUCCESS;
    }

    /**
     * @throws \Symfony\Component\Console\Exception\ExceptionInterface
     */
    private function scheduler(OutputInterface $output, ?string $pidFile): void
    {
        $input = new ArrayInput([]);

        $console = $this->getApplication();
        $command = $console->find('scheduler:execute');

        while (true) {
            $now = (int) microtime(false);
            usleep((int) ((60 - ($now % 60) + (int) $now - $now) * 1_000_000.0));

            if (null !== $pidFile && !file_exists($pidFile)) {
                break;
            }

            $command->run($input, $output);
        }
    }
}
