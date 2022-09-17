CommandSchedulerBundle
======================

[![Code_Checks](https://github.com/Dukecity/CommandSchedulerBundle/actions/workflows/code_checks.yaml/badge.svg?branch=master)](https://github.com/Dukecity/CommandSchedulerBundle/actions/workflows/code_checks.yaml)
[![codecov](https://codecov.io/gh/Dukecity/CommandSchedulerBundle/branch/master/graph/badge.svg?token=V3IZ35QH9D)](https://codecov.io/gh/Dukecity/CommandSchedulerBundle)

This bundle will allow you to easily manage scheduling for Symfony's console commands (native or not) with cron expression.
See [Wiki](https://github.com/Dukecity/CommandSchedulerBundle/wiki) for Details

## Versions & Dependencies

Version 5.x has the goal to use modern Php and Symfony features and low maintenance.
So only Php >= 8.0 and Symfony ^5.4|^6.0 are supported at the moment.

The following table shows the compatibilities of different versions of the bundle :

| Version                                                                        | Symfony        | PHP   |
|--------------------------------------------------------------------------------|----------------|-------|
| [5.0 (master)](https://github.com/Dukecity/CommandSchedulerBundle/tree/master) | ^5.4 + ^6.0    | >=8.0 |
| [4.x](https://github.com/Dukecity/CommandSchedulerBundle/tree/4.x)             | ^4.4.20 + ^5.3 | >=8.0 |
| [3.x](https://github.com/Dukecity/CommandSchedulerBundle/tree/3.x)             | ^4.4.20 + ^5.3 | >=7.3 |
| [2.2.x](https://github.com/Dukecity/CommandSchedulerBundle/tree/2.2)           | ^3.4 + ^4.3    | ^7.1  |


## Install

When using Symfony Flex there is an [installation recipe](https://github.com/symfony/recipes-contrib/tree/master/dukecity/command-scheduler-bundle/3.0).  
To use it, you have to enable contrib recipes on your project : 

    composer config extra.symfony.allow-contrib true
    composer req dukecity/command-scheduler-bundle

#### Update Database

If you're using DoctrineMigrationsBundle (recommended way):

    php bin/console make:migration
    php bin/console doctrine:migrations:migrate

Without DoctrineMigrationsBundle:

    php bin/console doctrine:schema:update --force

#### Install Assets

    php bin/console assets:install --symlink --relative public

#### Secure your route
Add this line to your security config.

    - { path: ^/command-scheduler, role: ROLE_ADMIN } 

Check new URL /command-scheduler/list

## Features

### New in Version 5:
- Drop support of Symfony < 5.4

### New in Version 4:
- API for all functions (in development)
- Event-Handling (preExecution, postExecution). You can subscribe to this [Events](Resources/doc/integrations/events/index.md)
- Monitoring: Optional Notifications with the [Symfony Notifier](https://symfony.com/doc/current/notifier.html) Component. Default: E-Mail
- Refactored Execution of Commands to Services. You can use them now from other Services.
- Handled error in Command Parsing. So there is no 500 Error while parsing commands.
- You CLI-commands for add, remove and list scheduled commands
- Improved UI of command-execution in cli

### Version 3:
- An admin interface to add, edit, enable/disable or delete scheduled commands.
- For each command, you define :
  - name
  - symfony console command (choice based on native `list` command)
  - cron expression (see [Cron format](http://en.wikipedia.org/wiki/Cron#Format) for informations)
  - output file (for `$output->write`)
  - priority
- A new console command `scheduler:execute [--dump] [--no-output]` which will be the single entry point to all commands
- Management of queuing and prioritization between tasks
- Locking system, to stop scheduling a command that has returned an error
- Monitoring with timeout or failed commands (Json URL and command with mailing)
- Translated in french, english, german and spanish
- An [EasyAdmin](https://github.com/EasyCorp/EasyAdminBundle) configuration template available [here](Resources/doc/integrations/easyadmin/index.md)
- **Beta** - Handle commands with a daemon (unix only) if you don't want to use a cronjob

## Screenshots
![list](Resources/doc/images/scheduled-list.png)

![new](Resources/doc/images/new-schedule.png)

![new2](Resources/doc/images/command-list.png)

## Documentation

See the [documentation here](https://github.com/Dukecity/CommandSchedulerBundle/wiki).

## License

This bundle is under the MIT license. See the [complete license](Resources/meta/LICENCE) for info.
