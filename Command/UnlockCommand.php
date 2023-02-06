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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to unlock one or all scheduled commands that have surpassed the lock timeout.
 *
 * @author  Marcel Pfeiffer <m.pfeiffer@strucnamics.de>
 */
#[AsCommand(name: 'scheduler:unlock', description: 'Unlock one or all scheduled commands that have surpassed the lock timeout.')]
class UnlockCommand extends Command
{
    private ObjectManager $em;
    public const DEFAULT_LOCK_TIME = 3600; // 1 hour
    private SymfonyStyle $io;

    private bool $unlockAll;
    private string | null $scheduledCommandName = null;

    /**
     * UnlockCommand constructor.
     *
     * @param ManagerRegistry $managerRegistry
     * @param string          $managerName
     * @param int             $lockTimeout     Number of seconds after a command is considered as timeout
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        string $managerName,
        private int $lockTimeout = self::DEFAULT_LOCK_TIME
    ) {
        $this->em = $managerRegistry->getManager($managerName);

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the command to unlock')
            ->addOption('all', 'A', InputOption::VALUE_NONE, 'Unlock all scheduled commands')
            ->addOption(
                'lock-timeout',
                null,
                InputOption::VALUE_REQUIRED,
                'Use this lock timeout value instead of the configured one (in seconds, optional)'
            );
    }

    /**
     * Initialize parameters and services used in execute function.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->unlockAll = (bool) $input->getOption('all');
        $this->scheduledCommandName = (string) $input->getArgument('name');

        $this->lockTimeout = (int)$input->getOption('lock-timeout');

        if (0 === $this->lockTimeout) {
            $this->lockTimeout = self::DEFAULT_LOCK_TIME;
        }

        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->unlockAll && empty($this->scheduledCommandName)) {
            $this->io->error('Either the name of a scheduled command or the --all option must be set.'.
                        PHP_EOL.'List all locked Commands: php console scheduler:monitor --dump');

            return Command::FAILURE;
        }

        $repository = $this->em->getRepository(ScheduledCommand::class);

        if ($this->unlockAll) {
            // Unlock all locked commands
            $failedCommands = $repository->findLockedCommand();

            if ($failedCommands) {
                foreach ($failedCommands as $failedCommand) {

                    // @see https://github.com/Dukecity/CommandSchedulerBundle/issues/46
                    if ($failedCommand->getCommand() !== self::getDefaultName()) {
                        $this->unlock($failedCommand);
                    }
                }
            }
        } else {
            $scheduledCommand = $repository->findOneBy(['name' => $this->scheduledCommandName, 'disabled' => false]);
            if (null === $scheduledCommand) {
                $this->io->error(
                    sprintf(
                        'Scheduled Command with name "%s" not found or is disabled.',
                        $this->scheduledCommandName
                    )
                );

                return Command::FAILURE;
            }

            $this->unlock($scheduledCommand);
        }

        $this->em->flush();

        return Command::SUCCESS;
    }

    /**
     * @throws \Exception
     */
    protected function unlock(ScheduledCommand $command): bool
    {
        if (!$command->isLocked()) {
            $this->io->warning(sprintf('Skipping: Scheduled Command "%s" is not locked.', $command->getName()));

            return false;
        }

        if ($this->lockTimeout &&
            null !== $command->getLastExecution() &&
            $command->getLastExecution() >= (new \DateTime())->sub(
                new \DateInterval(sprintf('PT%dS', $this->lockTimeout))
            )
        ) {
            $this->io->error(
                sprintf('Skipping: Timeout for scheduled Command "%s" has not run out.', $command->getName())
            );

            return false;
        }

        $command->setLocked(false);
        $this->io->success(sprintf('Scheduled Command "%s" has been unlocked.', $command->getName()));

        return true;
    }
}
