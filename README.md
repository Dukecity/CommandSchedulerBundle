CommandSchedulerBundle
======================

[![Code_Checks](https://github.com/Dukecity/CommandSchedulerBundle/actions/workflows/code_checks.yaml/badge.svg?branch=main)](https://github.com/Dukecity/CommandSchedulerBundle/actions/workflows/code_checks.yaml)
[![codecov](https://codecov.io/gh/Dukecity/CommandSchedulerBundle/branch/main/graph/badge.svg?token=V3IZ35QH9D)](https://codecov.io/gh/Dukecity/CommandSchedulerBundle)

This bundle will allow you to easily manage scheduling for Symfony's console commands (native or not) with cron expression.
See [Wiki](https://github.com/Dukecity/CommandSchedulerBundle/wiki) for Details

## Versions & Dependencies

Please read [Upgrade-News for Version 6](UPGRADE.md)

Version 6.x (unreleased) has the goal to use modern Php and Symfony features and low maintenance.
So only Php >= 8.2 and Symfony ^7.0 are supported at the moment.

The following table shows the compatibilities of different versions of the bundle :

| Version                                                                    | Symfony        | PHP   |
|----------------------------------------------------------------------------|----------------|-------|
| [6.x (main)](https://github.com/Dukecity/CommandSchedulerBundle/tree/main) | ^7.0           | >=8.2 |
| [5.x](https://github.com/Dukecity/CommandSchedulerBundle/tree/5.x)         | ^5.4 + ^6.0    | >=8.0 |
| [4.x](https://github.com/Dukecity/CommandSchedulerBundle/tree/4.x)         | ^4.4.20 + ^5.3 | >=8.0 |
| [3.x](https://github.com/Dukecity/CommandSchedulerBundle/tree/3.x)         | ^4.4.20 + ^5.3 | >=7.3 |
| [2.2.x](https://github.com/Dukecity/CommandSchedulerBundle/tree/2.2)       | ^3.4 + ^4.3    | ^7.1  |


## Install

When using Symfony Flex there is an [installation recipe](https://github.com/symfony/recipes-contrib/tree/main/dukecity/command-scheduler-bundle/3.0).  
To use it, you have to enable contrib recipes on your project : 

```sh
composer config extra.symfony.allow-contrib true
composer req dukecity/command-scheduler-bundle
```

#### Update Database

If you're using DoctrineMigrationsBundle (recommended way):

```sh
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

Without DoctrineMigrationsBundle:

```sh
php bin/console doctrine:schema:update --force
```

#### Install Assets

```sh
php bin/console assets:install --symlink --relative public
```

#### Secure your route
Add this line to your security config.

    - { path: ^/command-scheduler, role: ROLE_ADMIN } 

Check new URL /command-scheduler/list

## Features and Changelog

Please read [Changelog](CHANGELOG.md)

## Screenshots
![list](Resources/doc/images/scheduled-list.png)

![new](Resources/doc/images/new-schedule.png)

![new2](Resources/doc/images/command-list.png)

## Documentation

See the [documentation here](https://github.com/Dukecity/CommandSchedulerBundle/wiki).

## License

This bundle is under the MIT license. See the [complete license](Resources/meta/LICENCE) for info.
