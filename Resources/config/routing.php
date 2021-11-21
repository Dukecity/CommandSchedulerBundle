<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->add('dukecity_command_scheduler_list', '/command-scheduler/list')
        ->defaults(['_controller' => 'Dukecity\CommandSchedulerBundle\Controller\ListController::indexAction']);

    $routingConfigurator->add('dukecity_command_scheduler_monitor', '/command-scheduler/monitor')
        ->defaults(['_controller' => 'Dukecity\CommandSchedulerBundle\Controller\ApiController::monitorAction']);

    $routingConfigurator->add('dukecity_command_scheduler_api_list', '/command-scheduler/api/list')
        ->defaults(['_controller' => 'Dukecity\CommandSchedulerBundle\Controller\ApiController::listAction']);

    $routingConfigurator->add('dukecity_command_scheduler_api_console_commands', '/command-scheduler/api/console_commands')
        ->defaults(['_controller' => 'Dukecity\CommandSchedulerBundle\Controller\ApiController::getConsoleCommands']);

    $routingConfigurator->add('dukecity_command_scheduler_api_console_commands_details', '/command-scheduler/api/console_commands_details/{commands}')
        ->defaults(
            ['_controller' => 'Dukecity\CommandSchedulerBundle\Controller\ApiController::getConsoleCommandsDetails',
             'commands' => 'all'
            ]);

    $routingConfigurator->add('dukecity_command_scheduler_api_translate_cron_expression',
            '/command-scheduler/api/trans_cron_expression/{cronExpression}/{lang}')
        ->defaults(
            ['_controller' => 'Dukecity\CommandSchedulerBundle\Controller\ApiController::translateCronExpression',
                'lang' => 'en'
            ]);

    $routingConfigurator->add('dukecity_command_scheduler_action_toggle', '/command-scheduler/action/toggle/{id}')
        ->defaults(['_controller' => 'Dukecity\CommandSchedulerBundle\Controller\ListController::toggleAction']);

    $routingConfigurator->add('dukecity_command_scheduler_action_remove', '/command-scheduler/action/remove/{id}')
        ->defaults(['_controller' => 'Dukecity\CommandSchedulerBundle\Controller\ListController::removeAction']);

    $routingConfigurator->add('dukecity_command_scheduler_action_execute', '/command-scheduler/action/execute/{id}')
        ->defaults(['_controller' => 'Dukecity\CommandSchedulerBundle\Controller\ListController::executeAction']);

    $routingConfigurator->add('dukecity_command_scheduler_action_unlock', '/command-scheduler/action/unlock/{id}')
        ->defaults(['_controller' => 'Dukecity\CommandSchedulerBundle\Controller\ListController::unlockAction']);

    $routingConfigurator->add('dukecity_command_scheduler_detail_edit', '/command-scheduler/detail/edit/{id}')
        ->defaults(['_controller' => 'Dukecity\CommandSchedulerBundle\Controller\DetailController::edit']);

    $routingConfigurator->add('dukecity_command_scheduler_detail_new', '/command-scheduler/detail/edit')
        ->defaults(['_controller' => 'Dukecity\CommandSchedulerBundle\Controller\DetailController::edit']);
};
