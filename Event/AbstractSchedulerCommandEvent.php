<?php

namespace Dukecity\CommandSchedulerBundle\Event;

use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;

abstract class AbstractSchedulerCommandEvent
{
    public function __construct(private readonly ScheduledCommand $command)
    {
    }

    public function getCommand(): ScheduledCommand
    {
        return $this->command;
    }
}
