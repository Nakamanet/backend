## Lancer les tests

```bash
make test-db     # une seule fois : démarre la base de test et crée son schéma
make test        # ou : php artisan test
```

`make test-db` lance le conteneur `postgres_test` (port 5433), charge le schéma de
base depuis `dump/laravel.sql`, puis applique les migrations par-dessus. Les tables
de base ne sont **pas** dans les migrations : un `migrate:fresh` seul ne suffit pas.

Les données du conteneur vivent en RAM (`tmpfs`) : `make test-db-reset` repart d'une
base vierge. La config DB des tests est figée dans `phpunit.xml` avec `force="true"`,
pour qu'un run de tests ne puisse jamais atteindre la base Neon de `.env`.

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

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
