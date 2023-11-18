# Upgrade to 6.0

There are new fields for the database.
Please run migrations. (see README.md)

```sh
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```
