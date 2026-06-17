## Doctrine migrations

- Migrations are auto-generated
- You should not create any migrations manually, but you can edit generated ones

```bash
# Create a new migration
php bin/console make:migration

# Generate SQL from migration
php bin/console doctrine:migrations:migrate --write-sql --dry-run

# To run all migrations that are not yet applied
php bin/console doctrine:migrations:migrate

# Use the prev or next option to increment or decrement migrations
php bin/console doctrine:migrations:migrate prev
php bin/console doctrine:migrations:migrate next

# Use list and status to view available migrations and extended info about migrations
php bin/console doctrine:migrations:list
php bin/console doctrine:migrations:status
```