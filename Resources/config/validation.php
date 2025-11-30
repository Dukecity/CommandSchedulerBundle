<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // Register custom validator constraint namespace
    $containerConfigurator->extension('namespaces', [
        'CommandSchedulerConstraints' => 'Dukecity\CommandSchedulerBundle\Validator\Constraints\\',
    ]);

    // Note: Validation constraints are defined via PHP attributes on BaseScheduledCommand.
    // All custom entities MUST extend BaseScheduledCommand to inherit these constraints.
};
