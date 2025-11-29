# Upgrade to 6.0

There are new fields for the database.
Please run migrations. (see README.md)

```sh
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

## Migrating to a Custom Entity

Version 6 introduces extensible entity architecture. If you want to extend the ScheduledCommand entity with custom fields:

1. **Create your custom entity** extending `BaseScheduledCommand`:

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Dukecity\CommandSchedulerBundle\Entity\BaseScheduledCommand;

#[ORM\Entity]
#[ORM\Table(name: 'scheduled_command')]
class MyScheduledCommand extends BaseScheduledCommand
{
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $customField = null;

    // getters/setters...
}
```

2. **Configure the bundle** to use your entity:

```yaml
# config/packages/dukecity_command_scheduler.yaml
dukecity_command_scheduler:
    scheduled_command_class: App\Entity\MyScheduledCommand
```

3. **Create a migration** for any custom columns:

```sh
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

**Note:** When you configure a custom entity class, the bundle automatically excludes its default `ScheduledCommand` entity from Doctrine mappings to prevent table name conflicts.

**Important:** The base table structure (`scheduled_command`) is unchanged, so your existing data will continue to work with your custom entity class. Only custom fields you add require a migration.
