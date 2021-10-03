<?php

namespace Dukecity\CommandSchedulerBundle\Fixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;

/**
 * Class LoadScheduledCommandData.
 *
 * @author  Julien Guyon <julienguyon@hotmail.com>
 */
class LoadScheduledCommandData implements FixtureInterface
{
    protected ?ObjectManager $manager = null;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $now = new \DateTime();
        $today = clone $now;
        $beforeYesterday = $now->modify('-2 days');

        $this->createScheduledCommand('CommandTestOne',   'debug:container', '--help', '@daily', 'one.log', 100, $beforeYesterday);
        $this->createScheduledCommand('CommandTestTwo',   'debug:container', '', '@daily', 'two.log', 80, $beforeYesterday, true);
        $this->createScheduledCommand('CommandTestThree', 'debug:container', '', '@daily', 'three.log', 60, $today, false, true);
        $this->createScheduledCommand('CommandTestFour',   'debug:router', '', '@daily', 'four.log', 40, $today, false, false, true, -1);
        $this->createScheduledCommand('CommandTestFive',   'scheduler:test', '0 true', '@daily', 'five.log', 39, $today, false, false, true);
    }

    /**
     * Create a new ScheduledCommand in database.
     */
    protected function createScheduledCommand(
        string $name,
        string $command,
        string $arguments,
        string $cronExpression,
        string $logFile,
        int $priority = 0,
        ?\DateTime $lastExecution = null,
        bool $locked = false,
        bool $disabled = false,
        bool $executeNow = false,
        ?int $lastReturnCode = null
    ): bool {
        $this->manager->getConnection()->beginTransaction();
        try {
            $scheduledCommand = new ScheduledCommand();
            $scheduledCommand
            ->setName($name)
            ->setCommand($command)
            ->setArguments($arguments)
            ->setCronExpression($cronExpression)
            ->setLogFile($logFile)
            ->setPriority($priority)
            ->setLastExecution($lastExecution)
            ->setLocked($locked)
            ->setDisabled($disabled)
            ->setLastReturnCode($lastReturnCode)
            ->setExecuteImmediately($executeNow);

            $this->manager->persist($scheduledCommand);
            $this->manager->flush();
            $this->manager->getConnection()->commit();
        } catch (\Exception $e) {
            #var_dump($e->getMessage());
            $this->manager->getConnection()->rollBack();

            return false;
        }

        return true;
    }
}
