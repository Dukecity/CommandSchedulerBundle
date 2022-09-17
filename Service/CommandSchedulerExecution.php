<?php

namespace Dukecity\CommandSchedulerBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\Event\SchedulerCommandPostExecutionEvent;
use Dukecity\CommandSchedulerBundle\Event\SchedulerCommandPreExecutionEvent;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Command\Command;

class CommandSchedulerExecution
{
    private string $env;
    private null|string $logPath;
    #private EntityManagerInterface $em;
    private ObjectManager $em;
    private Application $application;

    public function __construct(
        private KernelInterface          $kernel,
        protected ParameterBagInterface  $parameterBag,
        private ?LoggerInterface         $logger,
        private EventDispatcherInterface $eventDispatcher,
        private ManagerRegistry          $managerRegistry,
        string                           $managerName
        )
    {
        $this->em = $managerRegistry->getManager($managerName);
        $this->logPath = $this->parameterBag->get('dukecity_command_scheduler.log_path');

        $this->application = new Application($kernel);
        $this->application->setAutoExit(false);
    }


    private function getCommand(ScheduledCommand $scheduledCommand): ?Command
    {
        try {
            $command = $this->application->find($scheduledCommand->getCommand());
        } catch (\InvalidArgumentException) {

            return null;
        }

        return $command;
    }

    private function getLog(
        ScheduledCommand $scheduledCommand,
        int $commandsVerbosity = OutputInterface::OUTPUT_NORMAL
        ): OutputInterface
    {
        // Use a StreamOutput or NullOutput to redirect write() and writeln() in a log file
        if (!$this->logPath || empty($scheduledCommand->getLogFile())) {
            $logOutput = new NullOutput();
        } else {
            // log into a file
            $logOutput = new StreamOutput(
                fopen(
                    $this->logPath.$scheduledCommand->getLogFile(),
                    'ab',
                    false
                ),
                $commandsVerbosity
            );
        }

        return $logOutput;
    }

    /**
     * - Find command
     */
    private function prepareCommandExecution(ScheduledCommand $scheduledCommand): ?Command
    {
        if(!($command = $this->getCommand($scheduledCommand)))
        {
            $scheduledCommand->setLastReturnCode(-1);
            #$this->output->writeln('<error>Cannot find '.$scheduledCommand->getCommand().'</error>');
        }

        return $command;
    }


    /**
     * Get Input Command
     * - call the command with args and environment
     * - merge the definition of the commands
     * - Disable interactive mode
     */
    private function getInputCommand(ScheduledCommand $scheduledCommand, Command $command, string $env): StringInput
    {
        $inputCommand = new StringInput(
            $scheduledCommand->getCommand().' '.$scheduledCommand->getArguments().' --env='.$env
        );

        # call the command with args and environment
        /*$inputCommand = new ArrayInput(array_merge(
            ['command' => $scheduledCommand->getCommand()],
            $scheduledCommand->getArguments(),
            ['--env' => $env],
        ));*/

        $command->mergeApplicationDefinition();
        $inputCommand->bind($command->getDefinition());

        // Disable interactive mode if the current command has no-interaction flag
        if ($inputCommand->hasParameterOption(['--no-interaction', '-n'])) {
            $inputCommand->setInteractive(false);
        }

        return $inputCommand;
    }


    /**
     * Do the real execution of a command
     */
    private function doExecution(ScheduledCommand $scheduledCommand, int $commandsVerbosity): int
    {
        $command = $this->prepareCommandExecution($scheduledCommand);

        $input = $this->getInputCommand($scheduledCommand, $command, $this->env);

        $logOutput = $this->getLog($scheduledCommand, $commandsVerbosity);

        $startRun = new \DateTimeImmutable();
        $exception = null;

        // Execute command and get return code
        try {
            $this->eventDispatcher->dispatch(new SchedulerCommandPreExecutionEvent($scheduledCommand));

            $result = $command->run($input, $logOutput);

            $this->em->clear();
        } catch (\Throwable $e) {
            $exception = $e;
            $logOutput->writeln($e->getMessage());
            $logOutput->writeln($e->getTraceAsString());
            $result = -1;
        } finally {
            $endRun = new \DateTimeImmutable();

            $profiling = [
                "startRun" => $startRun,
                "endRun"   => $endRun,
                "runtime" => $startRun->diff($endRun),
                ];

            $this->eventDispatcher->dispatch(new SchedulerCommandPostExecutionEvent($scheduledCommand, $result, $logOutput, $profiling, $exception));
        }

        return $result;
    }


    private function prepareExecution(ScheduledCommand $scheduledCommand): void
    {
        //reload command from database before every execution to avoid parallel execution
        $this->em->getConnection()->beginTransaction();
        try {
            $notLockedCommand = $this
                ->em
                ->getRepository(ScheduledCommand::class)
                ->getNotLockedCommand($scheduledCommand);

            //$notLockedCommand will be locked for avoiding parallel calls:
            // http://dev.mysql.com/doc/refman/5.7/en/innodb-locking-reads.html
            if (null === $notLockedCommand) {
                throw new \RuntimeException();
            }

            $scheduledCommand = $notLockedCommand;
            $scheduledCommand->setLastExecution(new \DateTime());
            $scheduledCommand->setLocked(true);
            $this->em->persist($scheduledCommand);
            $this->em->flush();
            $this->em->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->em->getConnection()->rollBack();
            /*$this->output->writeln(
                sprintf(
                    '<error>Command %s is locked %s</error>',
                    $scheduledCommand->getCommand(),
                    (empty($e->getMessage()) ? '' : sprintf('(%s)', $e->getMessage()))
                )
            );*/

            return;
        }
    }

    public function executeCommand(
        ScheduledCommand $scheduledCommand,
        string $env,
        string $commandsVerbosity = OutputInterface::VERBOSITY_NORMAL): int
    {
        $this->env = $env;
        $this->prepareExecution($scheduledCommand);

        /** @var ScheduledCommand $scheduledCommand */
        $scheduledCommand = $this->em->find(ScheduledCommand::class, $scheduledCommand);

        $result = $this->doExecution($scheduledCommand, $commandsVerbosity);

        if (false === $this->em->isOpen()) {
            #$this->output->writeln('<comment>Entity manager closed by the last command.</comment>');
            $this->em = $this->em::create($this->em->getConnection(), $this->em->getConfiguration());
        }

        // Reactivate the command in DB
        /** @var ScheduledCommand $scheduledCommand */
        $scheduledCommand = $this->em->find(ScheduledCommand::class, $scheduledCommand);

        $scheduledCommand->setLastReturnCode($result);
        $scheduledCommand->setLocked(false);
        $scheduledCommand->setExecuteImmediately(false);
        $this->em->persist($scheduledCommand);
        $this->em->flush();

        /*
         * This clear() is necessary to avoid conflict between commands and to be sure that none entity are managed
         * before entering in a new command
         */
        $this->em->clear();

        unset($command);
        gc_collect_cycles();

        return $result;
    }
}
