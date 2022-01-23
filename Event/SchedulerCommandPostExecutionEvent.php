<?php

namespace Dukecity\CommandSchedulerBundle\Event;

use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Error;

class SchedulerCommandPostExecutionEvent extends AbstractSchedulerCommandEvent
{
    public function __construct(
        private ScheduledCommand $command,
        private int $result,
        private ?OutputInterface $log = null,
        private ?array $profiling = null,
        private \Exception|Error|null $exception = null)
    {
        parent::__construct($command);
    }

    public function getResult(): int
    {
        return $this->result;
    }

    public function getLog(): ?OutputInterface
    {
        return $this->log;
    }

    public function getProfiling(): ?array
    {
        return $this->profiling;
    }

    public function getRuntime(): ?\DateInterval
    {
        return $this->profiling["runtime"] ?? null;
    }

    public function getException(): \Exception|Error|null
    {
        return $this->exception;
    }
}
