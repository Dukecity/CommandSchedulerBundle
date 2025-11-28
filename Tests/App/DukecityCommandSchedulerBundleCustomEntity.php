<?php

namespace App\Tests\App;

use Dukecity\CommandSchedulerBundle\DukecityCommandSchedulerBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Test-only bundle that extends the original but skips entity mapping registration.
 * This allows us to use TestCustomScheduledCommand instead of ScheduledCommand.
 */
class DukecityCommandSchedulerBundleCustomEntity extends DukecityCommandSchedulerBundle
{
    public function build(ContainerBuilder $container): void
    {
        // Do NOT call parent::build() - skip entity mapping registration
        // The test config will map TestCustomScheduledCommand instead
    }
}
