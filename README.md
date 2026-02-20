# Project Name

This project is built with [Laravel](https://laravel.com).

## Requirements

- Docker
- Docker Compose

## Getting Started

Ensure the .env file is correctly configured before starting the containers (check with Remi).

The database service name must match db in your docker-compose.yml.

Start the containers in detached mode:

```bash
docker compose up -d
```

Access the database container:

```bash
docker compose exec db bash
```

Connect to PostgreSQL:

```bash
psql -U laravel
Database Migrations
```

If the migration files (located in app/database/migrations) are missing or have not been executed, run:

```bash
php artisan migrate
```

This command creates the database tables defined in the migration files.
