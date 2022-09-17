<?php

/** @noinspection PhpUnused */
/** @noinspection PhpMissingFieldTypeInspection */

namespace Dukecity\CommandSchedulerBundle\Command;

use Doctrine\Persistence\ObjectManager;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Service\CommandSchedulerExecution;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class ExecuteCommand : This class is the entry point to execute all scheduled command.
 *
 * @author  Julien Guyon <julienguyon@hotmail.com>
 */
#[AsCommand(name: 'scheduler:execute', description: 'Execute scheduled commands')]
class ExecuteCommand extends Command
{
    use LockableTrait;

    //private ObjectManager | EntityManager $em;
    private ObjectManager $em;
    private string $dumpMode;
    private ?int $commandsVerbosity = null;
    private OutputInterface $output;
    private InputInterface $input;
    private string $env;

    public function __construct(
        private CommandSchedulerExecution $commandSchedulerExecution,
        private EventDispatcherInterface $eventDispatcher,
        ManagerRegistry $managerRegistry,
        string $managerName,
        private string | bool $logPath
    ) {
        $this->em = $managerRegistry->getManager($managerName);

        // If logpath is not set to false, append the directory separator to it
        if (false !== $this->logPath) {
            $this->logPath = rtrim($this->logPath, '/\\').DIRECTORY_SEPARATOR;
        }

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Execute scheduled commands')
            ->addOption('dump', null, InputOption::VALUE_NONE, 'Display next execution')
            ->addOption('no-output', null, InputOption::VALUE_NONE, 'Disable output message from scheduler')
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> is the entry point to execute all scheduled command:

You can list the commands with last and next exceution time with
<info>php bin/console scheduler:list</info>

HELP
            );
    }

    /**
     * Initialize parameters and services used in execute function.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;
        $this->input = $input;

        $this->dumpMode = (string) $this->input->getOption('dump');

        try{
            $this->env = $this->input->getOption('env');
        }
        catch (\Exception)
        {
            $this->env = "test";
        }


        // Store the original verbosity before apply the quiet parameter
        $this->commandsVerbosity = $this->output->getVerbosity();

        if (true === $this->input->getOption('no-output')) {
            $this->output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /*
         * Be sure that there are no overlapping Execution of commands.
         * The command is released at the end of this function
         * @see https://symfony.com/doc/current/console/lockable_trait.html
         */
        if (!$this->lock()) {
            $this->output->writeln('The command is already running in another process.');

            return Command::SUCCESS;
        }

       # For Unittests ;(
       if(is_a($this->output, ConsoleOutput::class))
        {
            $sectionListing = $this->output->section();
            $sectionProgressbar = $this->output->section();
            $io = new SymfonyStyle($this->input, $sectionListing);
        }
       else
        {
            $sectionProgressbar = $this->output;
            $io = new SymfonyStyle($this->input, $this->output);
            #$this->env="test";
        }


        // Before continue, we check that the "log_path" is valid and writable (except for gaufrette)
        if (false !== $this->logPath &&
            !str_starts_with($this->logPath, 'gaufrette:') &&
            !is_writable($this->logPath)
        ) {
            $io->error(
                $this->logPath.' not found or not writable. Check `log_path` in your config.yml'
            );

            return Command::FAILURE;
        }

        $commandsToExecute = $this->em->getRepository(ScheduledCommand::class)
            ->findCommandsToExecute();
        $amountCommands = count($commandsToExecute);



        $io->title('Start : '.($this->dumpMode ? 'Dump' : 'Execute').' of '.$amountCommands.' scheduled command(s)');


        if (is_iterable($commandsToExecute) && $amountCommands >= 1)
        {
        # dry-run ?
        if ($this->input->getOption('dump'))
        {
            foreach ($commandsToExecute as $command)
            {
                $io->info($command->getName().': '.$command->getCommand().' '.$command->getArguments());
            }
        }
        else
        {
            # Execute
            #$sectionProgressbar = $this->output->section();
            $progress = new ProgressBar($sectionProgressbar);
            $progress->setMessage('Start');
            $progress->start($amountCommands);

                foreach ($commandsToExecute as $command) {

                    $progress->setMessage('Start Execution of '.$command->getCommand().' '.$command->getArguments());
                    $io->comment('Start Execution of '.$command->getCommand().' '.$command->getArguments());

                    $result = $this->commandSchedulerExecution->executeCommand($command, $this->env, $this->commandsVerbosity);

                if($result===0)
                {$io->success($command->getName().': '.$command->getCommand().' '.$command->getArguments());}
                else
                {$io->error($command->getName().': ERROR '.$result.': '.$command->getCommand().' '.$command->getArguments());}

                    $progress->advance();
                }

            $progress->finish();


            if(!is_a($this->output, StreamOutput::class))
            {$sectionProgressbar->clear();}

            $io->section('Finished Executions');

        }}
        else {
            $io->success('Nothing to do.');
        }


        $this->release();

        return Command::SUCCESS;
    }
}
