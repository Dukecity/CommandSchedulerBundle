<?php

namespace Dukecity\CommandSchedulerBundle\Event;

class SchedulerCommandFailedEvent
{
    public function __construct(private array $failedCommands = [])
    {
    }

    public function getFailedCommands(): array
    {
        return $this->failedCommands;
    }

    public function getMessage(): string
    {
        $message = '';
        foreach ($this->failedCommands as $command) {
            $message .= sprintf(
                "%s: returncode %s, locked: %s, last execution: %s\n",
                $command->getName(),
                $command->getLastReturnCode(),
                $command->getLocked(),
                $command->getLastExecution()->format('Y-m-d H:i')
            );
        }

        return $message;
    }
}
