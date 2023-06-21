<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace Dukecity\CommandSchedulerBundle\Command;

use Doctrine\Persistence\ObjectManager;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Add a command.
 *
 * @example php bin/console scheduler:add 'myCommand' 'debug:router' '' '@daily' 10, 'mycommand.log' false, false
 */
#[AsCommand(name: 'scheduler:add', description: 'Add a scheduled command')]
class AddCommand extends Command
{
    #use CommandReturnTrait;
    private ObjectManager $em;

    public function __construct(ManagerRegistry $managerRegistry, string $managerName)
    {
        $this->em = $managerRegistry->getManager($managerName);

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Add a scheduled command')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the command')
            ->addArgument('cmd', InputArgument::REQUIRED, 'command')
            ->addArgument('arguments', InputArgument::REQUIRED, 'arguments')
            ->addArgument('cronExpression', InputArgument::REQUIRED, 'cronExpression')
            ->addArgument('logFile', InputArgument::OPTIONAL, 'logFile')
            ->addArgument('priority', InputArgument::OPTIONAL, 'priority', 0)
            ->addArgument('executeImmediately', InputArgument::OPTIONAL, 'executeImmediately', false)
            ->addArgument('disabled', InputArgument::OPTIONAL, 'disabled', false)
            # TODO Think about Update?
            #->addOption("--force", "-f", null, 'Force override', null)
            ;
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $commandName = (string) $input->getArgument('name');
        $command = (string) $input->getArgument('cmd');
        $arguments = (string) $input->getArgument('arguments');
        $cronExpression = (string) $input->getArgument('cronExpression');
        $priority = (int) $input->getArgument('priority');
        $logFile = (string) $input->getArgument('logFile');
        $executeImmediately = (bool) $input->getArgument('executeImmediately');
        $disabled = (bool) $input->getArgument('disabled');

        try {
            $cmd = $this->em->getRepository(ScheduledCommand::class)
                        ->findOneBy(['name' => $commandName]);

            if (!$cmd) {
                $cmd = new ScheduledCommand();
                $cmd->setName($commandName)
                ->setCommand($command)
                ->setArguments($arguments)
                ->setCronExpression($cronExpression)
                ->setPriority($priority)
                ->setLogFile($logFile)
                ->setExecuteImmediately($executeImmediately)
                ->setDisabled($disabled);

                $this->em->persist($cmd);
                $this->em->flush();
            } else {
                $io->error(sprintf('Could not add the command %s (already exists)', $commandName));

                return Command::FAILURE;
            }

            $io->success(sprintf('The Command %s is added successfully', $commandName));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Could not add the command %s', $commandName));
            #var_dump($e->getMessage());

            return Command::FAILURE;
        }
    }
}
