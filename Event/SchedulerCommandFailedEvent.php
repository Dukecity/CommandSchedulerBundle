<?php

namespace Dukecity\CommandSchedulerBundle\Event;

use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommandInterface;

class SchedulerCommandFailedEvent
{
    /**
     * @param ScheduledCommandInterface[] $failedCommands
     */
    public function __construct(private readonly array $failedCommands = [])
    {
    }

    /**
     * @return ScheduledCommandInterface[]
     */
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
                $command->getLastExecution()?->format('Y-m-d H:i') ?? 'Never'
            );
        }

        return $message;
    }
}
