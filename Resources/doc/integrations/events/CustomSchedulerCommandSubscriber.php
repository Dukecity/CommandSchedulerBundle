<?php

namespace App\EventSubscriber;

use Dukecity\CommandSchedulerBundle\Event\SchedulerCommandCreatedEvent;
use Dukecity\CommandSchedulerBundle\Event\SchedulerCommandPostExecutionEvent;
use Dukecity\CommandSchedulerBundle\Event\SchedulerCommandFailedEvent;
use Dukecity\CommandSchedulerBundle\Event\SchedulerCommandPreExecutionEvent;
use Dukecity\CommandSchedulerBundle\EventSubscriber\SchedulerCommandSubscriber;

/**
 * Example to Subscribe to Events from the Dukecity\CommandSchedulerBundle
 */
class CustomSchedulerCommandSubscriber extends SchedulerCommandSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SchedulerCommandCreatedEvent::class => ['onScheduledCommandCreated',    -10],
            SchedulerCommandFailedEvent::class => ['onScheduledCommandFailed',     20],
            SchedulerCommandPreExecutionEvent::class => ['onScheduledCommandPreExecution',   10],
            SchedulerCommandPostExecutionEvent::class => ['onScheduledCommandPostExecution',   30],
        ];
    }

    public function onScheduledCommandCreated(SchedulerCommandCreatedEvent $event): void
    {
        $this->logger->info('CustomScheduledCommandCreated', ['name' => $event->getCommand()->getName()]);
    }

    public function onScheduledCommandFailed(SchedulerCommandFailedEvent $event): void
    {
        $this->logger->warning('CustomSchedulerCommandFailedEvent', ['details' => $event->getMessage()]);
    }

    public function onScheduledCommandPreExecution(SchedulerCommandPreExecutionEvent $event): void
    {
        $this->logger->info('CustomScheduledCommandPreExecution', ['name' => $event->getCommand()->getName()]);
    }

    public function onScheduledCommandPostExecution(SchedulerCommandPostExecutionEvent $event): void
    {
        $this->logger->info('CustomScheduledCommandPostExecution', [
            'name' => $event->getCommand()->getName(),
            "result" => $event->getResult(),
            "runtime" => $event->getRuntime()->format('%S seconds'),
        ]);
    }
}
