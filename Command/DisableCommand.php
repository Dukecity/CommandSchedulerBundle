<?php

namespace Dukecity\CommandSchedulerBundle\Command;

use Doctrine\Persistence\ObjectManager;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to disable one or all scheduled commands
 */
#[AsCommand(name: 'scheduler:disable', description: 'Command to disable one or all scheduled commands')]
class DisableCommand extends Command
{
    private ObjectManager $em;
    private SymfonyStyle $io;

    private bool $disableAll;
    private string|null $scheduledCommandName = null;

    public function __construct(ManagerRegistry $managerRegistry,
                                string $managerName)
    {
        $this->em = $managerRegistry->getManager($managerName);

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the command to disable')
            ->addOption('all', 'A', InputOption::VALUE_NONE, 'Disable all scheduled commands')
            ;
    }

    /**
     * Initialize parameters and services used in execute function.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->disableAll = (bool) $input->getOption('all');
        $this->scheduledCommandName = (string) $input->getArgument('name');

        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->disableAll && empty($this->scheduledCommandName)) {
            $this->io->error('Either the name of a scheduled command or the --all option must be set.');

            return Command::FAILURE;
        }

        $repository = $this->em->getRepository(ScheduledCommand::class);

        # disable ALL
        if ($this->disableAll) {
            // disable all commands
            $commands = $repository->findAll();

            if ($commands) {
                foreach ($commands as $command) {

                    // @see https://github.com/Dukecity/CommandSchedulerBundle/issues/46
                    if ($command->getCommand() !== self::getDefaultName()) {
                        $this->disable($command);
                    }
                }
            }
        } else {
            # disable one
            $scheduledCommand = $repository->findOneBy(['name' => $this->scheduledCommandName]);

            if (null === $scheduledCommand) {
                $this->io->error(
                    sprintf(
                        'Scheduled Command with name "%s" not found.',
                        $this->scheduledCommandName
                    )
                );

                return Command::FAILURE;
            }

            # only if it is not already disabled
            if(!$scheduledCommand->isDisabled())
            {
             $this->disable($scheduledCommand);
            }
        }

        $this->em->flush();

        return Command::SUCCESS;
    }

    /**
     * @throws \Exception
     */
    protected function disable(ScheduledCommand $command): void
    {
        $command->setDisabled(true);

        $this->io->success(sprintf('Scheduled Command "%s" has been disabled.', $command->getName()));
    }
}
