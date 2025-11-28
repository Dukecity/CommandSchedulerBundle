<?php

namespace Dukecity\CommandSchedulerBundle\Event;

use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommandInterface;

abstract class AbstractSchedulerCommandEvent
{
    public function __construct(private readonly ScheduledCommandInterface $command)
    {
    }

    public function getCommand(): ScheduledCommandInterface
    {
        return $this->command;
    }
}
