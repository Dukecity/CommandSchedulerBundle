<?php

namespace Dukecity\CommandSchedulerBundle\Service;

use Cron\CronExpression;
use DateTime;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\TransactionRequiredException;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommandInterface;

/**
 * Service for querying scheduled commands.
 *
 * This service provides all query methods needed by the bundle,
 * independent of the repository class used by the entity.
 */
class ScheduledCommandQueryService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $scheduledCommandClass,
    ) {
    }

    /**
     * Find all enabled commands ordered by priority.
     *
     * @return ScheduledCommandInterface[]
     */
    public function findEnabledCommand(): array
    {
        return $this->em->getRepository($this->scheduledCommandClass)
            ->findBy(['disabled' => false, 'locked' => false], ['priority' => 'DESC']);
    }

    /**
     * Find all locked commands.
     *
     * @return ScheduledCommandInterface[]
     */
    public function findLockedCommand(): array
    {
        return $this->em->getRepository($this->scheduledCommandClass)
            ->findBy(['disabled' => false, 'locked' => true], ['priority' => 'DESC']);
    }

    /**
     * Find all failed commands.
     *
     * @return ScheduledCommandInterface[]
     */
    public function findFailedCommand(): array
    {
        return $this->em->createQueryBuilder()
            ->select('command')
            ->from($this->scheduledCommandClass, 'command')
            ->where('command.disabled = :disabled')
            ->andWhere('command.lastReturnCode != :lastReturnCode')
            ->setParameter('lastReturnCode', 0)
            ->setParameter('disabled', false)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all enabled commands that need to be executed ordered by priority.
     *
     * @return ScheduledCommandInterface[]
     *
     * @throws \Exception
     */
    public function findCommandsToExecute(): array
    {
        $enabledCommands = $this->findEnabledCommand();
        $commands = [];
        $now = new DateTime();

        foreach ($enabledCommands as $command) {
            if ($command->isExecuteImmediately()) {
                $commands[] = $command;
            } else {
                $cron = new CronExpression($command->getCronExpression());
                try {
                    $nextRunDate = $cron->getNextRunDate($command->getLastExecution());

                    if ($nextRunDate < $now) {
                        $commands[] = $command;
                    }
                } catch (\Exception $e) {
                    // Skip commands with invalid cron expressions
                }
            }
        }

        return $commands;
    }

    /**
     * Find all failed and timed out commands.
     *
     * @return ScheduledCommandInterface[]
     */
    public function findFailedAndTimeoutCommands(int|bool $lockTimeout = false): array
    {
        $failedCommands = $this->findFailedCommand();

        if (false !== $lockTimeout) {
            $lockedCommands = $this->findLockedCommand();
            foreach ($lockedCommands as $lockedCommand) {
                $now = time();
                if ($lockedCommand->getLastExecution()->getTimestamp() + $lockTimeout < $now) {
                    $failedCommands[] = $lockedCommand;
                }
            }
        }

        return $failedCommands;
    }

    /**
     * Get a command only if it's not locked, using pessimistic locking.
     *
     * @throws NonUniqueResultException
     * @throws TransactionRequiredException
     */
    public function getNotLockedCommand(ScheduledCommandInterface $command): ?ScheduledCommandInterface
    {
        $query = $this->em->createQueryBuilder()
            ->select('command')
            ->from($this->scheduledCommandClass, 'command')
            ->where('command.locked = false')
            ->andWhere('command.id = :id')
            ->setParameter('id', $command->getId())
            ->getQuery();

        $query->setLockMode(LockMode::PESSIMISTIC_WRITE);

        return $query->getOneOrNullResult();
    }
}
