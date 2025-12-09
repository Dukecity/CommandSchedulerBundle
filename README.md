CommandSchedulerBundle
======================

[![Code_Checks](https://github.com/Dukecity/CommandSchedulerBundle/actions/workflows/code_checks.yaml/badge.svg?branch=main)](https://github.com/Dukecity/CommandSchedulerBundle/actions/workflows/code_checks.yaml)
[![codecov](https://codecov.io/gh/Dukecity/CommandSchedulerBundle/branch/main/graph/badge.svg?token=V3IZ35QH9D)](https://codecov.io/gh/Dukecity/CommandSchedulerBundle)
![CodeRabbit Pull Request Reviews](https://img.shields.io/coderabbit/prs/github/Dukecity/CommandSchedulerBundle?utm_source=oss&utm_medium=github&utm_campaign=Dukecity%2FCommandSchedulerBundle&labelColor=171717&color=FF570A&link=https%3A%2F%2Fcoderabbit.ai&label=CodeRabbit+Reviews)

This bundle will allow you to easily manage scheduling for Symfony's console commands (native or not) with cron expression.
See [Wiki](https://github.com/Dukecity/CommandSchedulerBundle/wiki) for Details

## Versions & Dependencies

Please read [Upgrade-News for Version 6](UPGRADE.md)

Version 6.x has the goal to use modern Php and Symfony features and low maintenance.
So only Php >= 8.2 and Symfony ^7.0 (Latest: ^7.3) are supported at the moment.

The following table shows the compatibilities of different versions of the bundle :

| Version                                                                    | Symfony        | PHP   |
|----------------------------------------------------------------------------|----------------|-------|
| [6.x (main)](https://github.com/Dukecity/CommandSchedulerBundle/tree/main) | ^7.0 + ^8.0    | >=8.2 |
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

## Extending the ScheduledCommand Entity

You can extend the default `ScheduledCommand` entity to add custom fields or behavior. The bundle uses a MappedSuperclass pattern with an interface for maximum flexibility.

### Creating a Custom Entity

1. Create your custom entity extending `BaseScheduledCommand`:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Dukecity\CommandSchedulerBundle\Entity\BaseScheduledCommand;

#[ORM\Entity]
#[ORM\Table(name: 'scheduled_command')]
class MyScheduledCommand extends BaseScheduledCommand
{
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $customField = null;

    public function getCustomField(): ?string
    {
        return $this->customField;
    }

    public function setCustomField(?string $customField): static
    {
        $this->customField = $customField;
        return $this;
    }
}
```

2. Configure the bundle to use your custom entity:

```yaml
# config/packages/dukecity_command_scheduler.yaml
dukecity_command_scheduler:
    scheduled_command_class: App\Entity\MyScheduledCommand
```

3. Update your database schema:

```sh
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Available Extension Points

- `ScheduledCommandInterface` - Contract for all scheduled command entities
- `BaseScheduledCommand` - MappedSuperclass with all base properties and methods
- `ScheduledCommand` - Default concrete entity (used if no custom class configured)
- `ScheduledCommandFactory` - Service for creating entity instances

## Documentation

See the [documentation here](https://github.com/Dukecity/CommandSchedulerBundle/wiki).

## License

This bundle is under the MIT license. See the [complete license](Resources/meta/LICENCE) for info.
