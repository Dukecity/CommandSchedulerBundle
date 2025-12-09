<?php

namespace Dukecity\CommandSchedulerBundle\Service;

use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommandInterface;

/**
 * Factory for creating ScheduledCommand entities.
 * Uses the configured entity class to support custom implementations.
 */
class ScheduledCommandFactory
{
    /**
     * @param class-string<ScheduledCommandInterface> $entityClass
     */
    public function __construct(
        private readonly string $entityClass
    ) {
    }

    /**
     * Create a new scheduled command entity instance.
     */
    public function create(): ScheduledCommandInterface
    {
        return new $this->entityClass();
    }

    /**
     * Get the configured entity class name.
     *
     * @return class-string<ScheduledCommandInterface>
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
}
