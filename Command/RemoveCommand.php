<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace Dukecity\CommandSchedulerBundle\Command;

use Doctrine\Persistence\ObjectManager;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Remove a command.
 */
#[AsCommand(name: 'scheduler:remove', description: 'Remove a scheduled command', aliases:['scheduler:delete'])]
class RemoveCommand extends Command
{
    private ObjectManager $em;
    private SymfonyStyle $io;

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
        $this->setDescription('Remove a scheduled command. Hard delete from Database')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the command to remove')
            ->setHelp(<<<'HELP'
The <info>%command.name%</info> command deletes scheduled command from the database:

  <info>php %command.full_name%</info> <comment>name</comment>

If you omit the argument, the command will ask you to provide the missing value:

  <info>php %command.full_name%</info>

You can list all available commands with

  <info>php console scheduler:list</info>

HELP
            )
           ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument('name')) {
            return;
        }

        $this->io->title('Delete Scheduled Command Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' <info>$ php bin/console '.self::$defaultName.' name</info>',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
            '',
        ]);

        $name = $this->io->ask('Name of Scheduled Command');
        $input->setArgument('name', $name);
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $commandName = (string) $input->getArgument('name');

        try {
            $command = $this->em->getRepository(ScheduledCommand::class)->findOneBy(
                ['name' => $commandName]
            );

            $this->em->remove($command);
            $this->em->flush();

            $io->success(sprintf('The Command %s is deleted successfully', $commandName));

            return Command::SUCCESS;
        } catch (\Exception) {
            $io->error(sprintf('Could not find/delete the command %s', $commandName));

            return Command::FAILURE;
        }
    }

    /**
     * @return string[]
     */
    public function getCommandNames(): array
    {
        $return = [];
        $commands = $this->em->getRepository(ScheduledCommand::class)->findAll();
        foreach ($commands as $command){
            $return[] = $command->getName();
        }

        return $return;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('name')) {
            $suggestions->suggestValues($this->getCommandNames());
        }
    }
}
