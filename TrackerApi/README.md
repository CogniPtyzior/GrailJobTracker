# TrackerApi

Symfony 7.4 LTS backend for the GrailJob Tracker MVP.

## Local Docker workflow

The local Docker stack lives at the repository root:

- `compose.yaml`
- `docker/php/*`
- `docker/nginx/conf.d/tracker-api.local.conf`
- `.env.docker.local`

### Main commands

```bash
docker compose up --build
docker compose exec tracker-api-php php bin/console doctrine:migrations:migrate
docker compose exec tracker-api-php php bin/console app:bootstrap-admin
```

### Notes

- Nginx is published on `http://127.0.0.1:8081`
- PostgreSQL is expected on `host.docker.internal:5432`
- Database schema: `trackers`
- Session cookie lifetime: `4 hours`
- Bootstrap admin email: `admin@grailjob.local`
- Bootstrap password file on the host: `./credentials/password.secret`
