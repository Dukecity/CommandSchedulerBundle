<?php

namespace Dukecity\CommandSchedulerBundle\Repository;

use Cron\CronExpression;
use DateTimeInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\TransactionRequiredException;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommandInterface;

/**
 * Class ScheduledCommandRepository.
 *
 * @template-extends EntityRepository<ScheduledCommandInterface>
 * @author  Julien Guyon <julienguyon@hotmail.com>
 *
 * @deprecated The custom query methods in this repository are deprecated.
 *             Use ScheduledCommandQueryService instead, which works with any entity class.
 */
class ScheduledCommandRepository extends EntityRepository
{
    /**
     * Find all enabled command ordered by priority.
     *
     * @deprecated Use ScheduledCommandQueryService::findEnabledCommand() instead
     * @return ScheduledCommandInterface[]|null
     */
    public function findEnabledCommand(): ?array
    {
        return $this->findBy(['disabled' => false, 'locked' => false], ['priority' => 'DESC']);
    }

    /**
     * findAll override to implement the default orderBy clause.
     * @inheritdoc
     */
    public function findAll(): array
    {
        return $this->findBy([], ['disabled' => 'ASC', 'priority' => 'DESC']);
    }

    /**
     * Find all commands ordered by next run time
     *
     * @deprecated Use ScheduledCommandQueryService instead
     * @throws \Exception
     * @return ScheduledCommandInterface[]|null
     */
    public function findAllSortedByNextRuntime(): ?array
    {
        $allCommands = $this->findAll();
        $commands = [];
        $now = new \DateTime();
        $future = (new \DateTime())->add(new \DateInterval("P2Y"));
        $futureSort = $future->format(DateTimeInterface::ATOM);

        # execution is forced onetimes via isExecuteImmediately
        foreach ($allCommands as $command) {

            if($command->getDisabled() || $command->getLocked())
            {
                $commands[] = ["order" => $futureSort, "command" => $command];
                continue;
            }

            if ($command->isExecuteImmediately()) {

                $commands[] = ["order" => (new \DateTime())->format(DateTimeInterface::ATOM), "command" => $commands];
            } else {
                $cron = new CronExpression($command->getCronExpression());
                try {
                    $nextRunDate = $cron->getNextRunDate($command->getLastExecution());

                    if ($nextRunDate)
                    {$commands[] = ["order" => $nextRunDate->format(DateTimeInterface::ATOM), "command" => $command];}
                    else
                    {$commands[] = ["order" => $futureSort, "command" => $command];}

                } catch (\Exception $e) {
                   $commands[] = ["order" => $futureSort, "command" => $command];
                }
            }
        }

        # sort it by "order"
        usort($commands, static function($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        #var_dump($commands);

        $result = [];
        foreach($commands as $cmd)
        {$result[] = $cmd["command"];}

        #var_dump($result);

        return $result;
    }

    /**
     * Find all locked commands.
     *
     * @deprecated Use ScheduledCommandQueryService::findLockedCommand() instead
     * @return ScheduledCommandInterface[]
     */
    public function findLockedCommand(): array
    {
        return $this->findBy(['disabled' => false, 'locked' => true], ['priority' => 'DESC']);
    }

    /**
     * Find all failed command.
     *
     * @deprecated Use ScheduledCommandQueryService::findFailedCommand() instead
     * @return ScheduledCommandInterface[]|null
     */
    public function findFailedCommand(): ?array
    {
        return $this->createQueryBuilder('command')
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
     * @deprecated Use ScheduledCommandQueryService::findCommandsToExecute() instead
     * @throws \Exception
     * @return ScheduledCommandInterface[]|null
     */
    public function findCommandsToExecute(): ?array
    {
        $enabledCommands = $this->findEnabledCommand();
        $commands = [];
        $now = new \DateTime();

        # Get commands which runtime is in the past or
        # execution is forced onetimes via isExecuteImmediately
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
                }

            }
        }

        return $commands;
    }

    /**
     * @deprecated Use ScheduledCommandQueryService::findFailedAndTimeoutCommands() instead
     * @return ScheduledCommandInterface[]
     */
    public function findFailedAndTimeoutCommands(int | bool $lockTimeout = false): array
    {
        // Fist, get all failed commands (return != 0)
        $failedCommands = $this->findFailedCommand();

        // Then, si a timeout value is set, get locked commands and check timeout
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
     * @deprecated Use ScheduledCommandQueryService::getNotLockedCommand() instead
     * @throws NonUniqueResultException
     * @throws TransactionRequiredException
     */
    public function getNotLockedCommand(ScheduledCommandInterface $command): ScheduledCommandInterface | null
    {
        $query = $this->createQueryBuilder('command')
            ->where('command.locked = false')
            ->andWhere('command.id = :id')
            ->setParameter('id', $command->getId())
            ->getQuery();

        # https://www.doctrine-project.org/projects/doctrine-orm/en/2.8/reference/transactions-and-concurrency.html
        $query->setLockMode(LockMode::PESSIMISTIC_WRITE);

        return $query->getOneOrNullResult();
    }
}
