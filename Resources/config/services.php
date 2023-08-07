<?php

declare(strict_types=1);

use Dukecity\CommandSchedulerBundle\Command\AddCommand;
use Dukecity\CommandSchedulerBundle\Command\DisableCommand;
use Dukecity\CommandSchedulerBundle\Command\ExecuteCommand;
use Dukecity\CommandSchedulerBundle\Command\MonitorCommand;
use Dukecity\CommandSchedulerBundle\Command\RemoveCommand;
use Dukecity\CommandSchedulerBundle\Command\StartSchedulerCommand;
use Dukecity\CommandSchedulerBundle\Command\StopSchedulerCommand;
use Dukecity\CommandSchedulerBundle\Command\TestCommand;
use Dukecity\CommandSchedulerBundle\Command\UnlockCommand;
use Dukecity\CommandSchedulerBundle\Controller\DetailController;
use Dukecity\CommandSchedulerBundle\Controller\ApiController;
use Dukecity\CommandSchedulerBundle\Controller\ListController;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand;
use Dukecity\CommandSchedulerBundle\EventSubscriber\SchedulerCommandSubscriber;
use Dukecity\CommandSchedulerBundle\Form\Type\CommandChoiceType;
use Dukecity\CommandSchedulerBundle\Service\CommandParser;
use Dukecity\CommandSchedulerBundle\Command\ListCommand;
use Dukecity\CommandSchedulerBundle\Service\CommandSchedulerExecution;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->public()
        ->autowire(true);

    $services->set(DetailController::class)
        ->call('setManagerRegistry', [service('doctrine')])
        ->call('setManagerName', ['%dukecity_command_scheduler.doctrine_manager%'])
        ->call('setTranslator', [service('translator')])
        ->tag('container.service_subscriber')
        ->tag('controller.service_arguments');

    $services->set(ListController::class)
        ->call('setManagerRegistry', [service('doctrine')])
        ->call('setManagerName', ['%dukecity_command_scheduler.doctrine_manager%'])
        ->call('setTranslator', [service('translator')])
        ->call('setLockTimeout', ['%dukecity_command_scheduler.lock_timeout%'])
        ->call('setLogger', [service('logger')])
        ->tag('container.service_subscriber')
        ->tag('controller.service_arguments');

    $services->set(CommandParser::class)
        ->args(
            [
                service('kernel'),
                '%dukecity_command_scheduler.excluded_command_namespaces%',
                '%dukecity_command_scheduler.included_command_namespaces%',
            ]
        );

    $services->set(ApiController::class)
        ->call('setManagerRegistry', [service('doctrine')])
        ->call('setManagerName', ['%dukecity_command_scheduler.doctrine_manager%'])
        ->call('setTranslator', [service('translator')])
        ->call('setLockTimeout', ['%dukecity_command_scheduler.lock_timeout%'])
        ->call('setLogger', [service('logger')])
        ->call('setCommandParser', [service(CommandParser::class)])
        ->tag('container.service_subscriber')
        ->tag('controller.service_arguments')
    ;

    $services->set(CommandSchedulerExecution::class)
        ->args(
            [
                service('kernel'),
                service('parameter_bag'),
                service('logger'),
                service('event_dispatcher'),
                service('doctrine'),
                '%dukecity_command_scheduler.doctrine_manager%',
                '%dukecity_command_scheduler.log_path%',
            ]
        )
        #->alias("CommandSchedulerExecution")
    ;

    $services->set(CommandChoiceType::class)
        ->tag('form.type', ['alias' => 'command_choice']);

    $services->set(ExecuteCommand::class)
        ->args(
            [
                service(CommandSchedulerExecution::class),
                service('event_dispatcher'),
                service('doctrine'),
                '%dukecity_command_scheduler.doctrine_manager%',
                '%dukecity_command_scheduler.log_path%',
            ]
        )
        ->tag('console.command');

    $services->set(MonitorCommand::class)
        ->args(
            [
                service('event_dispatcher'),
                service('doctrine'),
                '%dukecity_command_scheduler.doctrine_manager%',
                '%dukecity_command_scheduler.lock_timeout%',
                '%dukecity_command_scheduler.monitor_mail%',
                '%dukecity_command_scheduler.monitor_mail_subject%',
                '%dukecity_command_scheduler.send_ok%',
            ]
        )
        ->tag('console.command');

    $services->set(ListCommand::class)
        ->args(
            [
                service('doctrine'),
                '%dukecity_command_scheduler.doctrine_manager%'
            ]
        )
        ->tag('console.command');

    $services->set(UnlockCommand::class)
        ->args(
            [
                service('doctrine'),
                '%dukecity_command_scheduler.doctrine_manager%',
                '%dukecity_command_scheduler.lock_timeout%',
            ]
        )
        ->tag('console.command');

    $services->set(AddCommand::class)
        ->args(
            [
                service('doctrine'),
                '%dukecity_command_scheduler.doctrine_manager%',
            ]
        )
        ->tag('console.command');

    $services->set(RemoveCommand::class)
        ->args(
            [
                service('doctrine'),
                '%dukecity_command_scheduler.doctrine_manager%',
            ]
        )
        ->tag('console.command');

    $services->set(StartSchedulerCommand::class)
        ->tag('console.command');

    $services->set(StopSchedulerCommand::class)
        ->tag('console.command');

    $services->set(TestCommand::class)
        ->tag('console.command');

    $services->set(ScheduledCommand::class)
        ->tag('controller.service_arguments');

    $services->set(DisableCommand::class)
        ->args(
            [
                service('doctrine'),
                '%dukecity_command_scheduler.doctrine_manager%'
            ]
        )
        ->tag('console.command');


    if(class_exists(\Symfony\Component\Notifier\NotifierInterface::class))
    {$notifier = service('notifier');}
    else { $notifier = null; }

    $services->set(SchedulerCommandSubscriber::class)
        ->args(
            [
                service('logger'),
                service('doctrine.orm.default_entity_manager'),
                $notifier,
                '%dukecity_command_scheduler.monitor_mail%',
                '%dukecity_command_scheduler.monitor_mail_subject%',
            ]
        )
        ->tag('kernel.event_subscriber');
};
