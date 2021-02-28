CommandSchedulerBundle
======================

[![Code_Checks](https://github.com/Chris53897/CommandSchedulerBundle/actions/workflows/code_checks.yaml/badge.svg?branch=master)](https://github.com/Chris53897/CommandSchedulerBundle/actions/workflows/code_checks.yaml)
[![Coverage Status](https://coveralls.io/repos/github/Chris53897/CommandSchedulerBundle/badge.svg?branch=master)](https://coveralls.io/github/Chris53897/CommandSchedulerBundle?branch=master)


This bundle will allow you to easily manage scheduling for Symfony's console commands (native or not) with cron expression.
See [Wiki](https://github.com/Chris53897/CommandSchedulerBundle/wiki) for Details

## Versions & Dependencies

Version 4.x has the goal to use modern Php and Symfony features and low maintenance.
So only Php >= 8.0 and Symfony >= 5.0 are supported at the moment.

The following table shows the compatibilities of different versions of the bundle :

| Version                                                                                 | Symfony          | PHP    |
| --------------------------------------------------------------------------------------- |  --------------- | ------ |
| [4.0 (master)](https://github.com/Chris53897/CommandSchedulerBundle/tree/master)        | ^5.0             | >=8.0  |
| [3.0 (master)](https://github.com/Chris53897/CommandSchedulerBundle/tree/master)        | ^3.4\|^4.3\|^5.0 | >=7.2  |
| [2.2.x](https://github.com/Chris53897/CommandSchedulerBundle/tree/2.2)                  | ^3.4\|^4.3       | >=7.1  |


## Install

When using Symfony Flex there is an [installation recipe](https://github.com/symfony/recipes-contrib/tree/master/jmose/command-scheduler-bundle/2.0).  
To use it, you have to enable contrib recipes on your project : `composer config extra.symfony.allow-contrib true`

composer req jmose/command-scheduler-bundle

Update Database
php bin/console make:migration
php bin/console doctrine:migrations:migrate

Secure your route by adding - { path: ^/command-scheduler, role: ROLE_ADMIN } in your security config.

Check new URL /command-scheduler/list

## Features

New in Version 4: 
- Event-Handling (preExcecution, postExceution). You can subscribe your own Events
- Monitoring: Notifications with the [Symfony Notifier](https://symfony.com/doc/current/notifier.html) Component. Derfault: E-Mail
- Refactored Exceution of Commands to Services. You can use them now from other Services.
- Handled error in Command Parsing. So there is no 500 Error while parsing commands. 
- You CLI-commands for add, remove and list scheduled commands
- Improved UI of command-exceutions in cli
- Create Command-Listing as Json for API usage


Version 3:
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
- An [EasyAdmin](https://github.com/EasyCorp/EasyAdminBundle) configuration template available [here](Resources/doc/index.md#6---easyadmin-integration)
- **Beta** - Handle commands with a deamon (unix only) if you don't want to use a cronjob

## Screenshots
![list](Resources/doc/images/scheduled-list.png)
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2FChris53897%2FCommandSchedulerBundle.svg?type=shield)](https://app.fossa.com/projects/git%2Bgithub.com%2FChris53897%2FCommandSchedulerBundle?ref=badge_shield)

![new](Resources/doc/images/new-schedule.png)

![new2](Resources/doc/images/command-list.png)

## Documentation

See the [documentation here](https://github.com/Chris53897/CommandSchedulerBundle/wiki).

## License

This bundle is under the MIT license. See the [complete license](Resources/meta/LICENCE) for info.


[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2FChris53897%2FCommandSchedulerBundle.svg?type=large)](https://app.fossa.com/projects/git%2Bgithub.com%2FChris53897%2FCommandSchedulerBundle?ref=badge_large)