# GrailJobTracker

GrailJobTracker is a full-stack job application tracker built as a personal project with production-like engineering constraints.

The application helps a user centralize an active job search: companies, roles, statuses, follow-up dates, interviews, contacts, notes, offer links, remote preferences and relevance scoring. It also includes an admin back office for users and access requests.

## Goals

The project is intentionally more structured than a simple CRUD demo. The backend is organized to keep business rules readable, testable and isolated from framework concerns where it matters, while still staying pragmatic with Symfony and Doctrine.

Main engineering goals:

- maintain a clear separation between presentation, application, domain and infrastructure code;
- keep domain rules close to aggregates and value objects;
- keep Symfony, Doctrine, Messenger and Mailer concerns in infrastructure or presentation adapters;
- expose a usable Angular application backed by a Symfony API;
- provide a reproducible Docker workflow for local and production-oriented execution;
- cover domain behavior, payload validation, authentication, access control and API flows with tests.

## Application Scope

### Candidate Tracking

Authenticated users can create, update, search, export and delete tracked job applications.

Tracked applications include:

- company and job title;
- contract type, location and remote mode;
- remuneration, offer URL and notes;
- application, follow-up and interview dates;
- HR and business contacts;
- subjective relevance score;
- lifecycle status.

The status can be inferred from the tracked job timeline, while final statuses such as hired, rejected and withdrawn remain explicit decisions.

### Access Requests

Visitors can submit an access request with an email, company name, reason and optional identity fields. Admin users can review, approve or delete those requests.

Access request notifications are dispatched asynchronously through Messenger and handled by infrastructure adapters.

### Administration

Admin users can manage users, activation state, admin role assignment and pending access requests.

## Architecture

The Symfony backend follows a modular structure by functional area:

```txt
TrackerApi/src/
|-- AccessRequest/
|-- Admin/
|-- ReferenceData/
|-- Security/
|-- Shared/
`-- TrackedJob/
```

Most modules are split into familiar layers:

```txt
Domain/          Aggregates, value objects, enums and repository ports
Application/     Use cases, commands, inputs, results and application ports
Infrastructure/  Doctrine records, mappers, repositories, security, mailer, messenger
Presentation/    Controllers, HTTP payloads, validators, presenters and view models
```

Important current choices:

- Doctrine persistence models are separated from domain entities.
- Domain entities do not carry Doctrine mapping attributes.
- `User` domain is decoupled from Symfony Security; `SecurityUser` is the Symfony adapter.
- `TrackedJob` owns only an owner `UserId`; Doctrine keeps the actual `TrackedJobRecord -> UserRecord` relation.
- Business-oriented value objects cover identifiers, names, access request reason, notes, offer URL, relevance score, roles and tracked job timeline.
- Presentation output uses typed view models before being serialized to JSON arrays.
- Framework-specific integrations such as password hashing, Messenger dispatch, message handling and email sending are kept behind application ports or in infrastructure.

This is not strict academic DDD. It is a pragmatic Symfony architecture designed to be understandable in a production team review.

## Tech Stack

### Backend

- PHP 8.4
- Symfony 7.4
- Doctrine ORM / DBAL / Migrations
- PostgreSQL
- Symfony Security
- Symfony Messenger with Doctrine transport
- Symfony Mailer
- Symfony Validator
- Nelmio API Doc
- PHPUnit 13

### Frontend

- Angular 20
- Angular Material
- TypeScript
- RxJS
- SCSS
- Vitest

### Infrastructure

- Docker Compose
- PHP-FPM
- Nginx
- external PostgreSQL reachable from containers through `host.docker.internal`
- environment-based configuration
- local secret files for admin and SMTP passwords

## Project Structure

```txt
GrailJobTracker/
|-- TrackerApi/              Symfony backend
|   |-- config/
|   |-- migrations/
|   |-- public/
|   |-- src/
|   |-- tests/
|   `-- composer.json
|
|-- TrackerApp/              Angular frontend
|   |-- public/
|   |-- src/
|   |-- angular.json
|   `-- package.json
|
|-- docker/                  PHP-FPM and Nginx images/configuration
|-- credentials/             Local secret files, ignored except placeholders
|-- compose.yaml             Local backend stack
|-- compose.production.yaml  Production-oriented stack
|-- .env.docker.sample       Docker environment example
`-- rebuild-app.sh           Production rebuild helper
```

## Local Docker Workflow

Create a local environment file from the sample:

```bash
cp .env.docker.sample .env.docker.local
```

Provide the expected local secret files:

```txt
credentials/password.secret
credentials/smtp/password.secret
```

Start or rebuild the backend stack:

```bash
docker compose up --build -d
```

The API is exposed through Nginx at:

```txt
http://127.0.0.1:8081
```

Useful backend commands:

```bash
docker compose exec tracker-api-php php bin/console doctrine:migrations:migrate
docker compose exec tracker-api-php php bin/console app:bootstrap-admin
docker compose exec tracker-api-php ./vendor/bin/phpunit
```

The Messenger worker runs in a dedicated `tracker-api-worker` container.

## Frontend Workflow

From `TrackerApp`:

```bash
npm install
npm start
npm test
npm run build
```

The Angular dev server runs on port `4200` by default.

## Testing

Backend tests cover unit and integration behavior across the main modules:

- domain entities and value objects;
- tracked job status and timeline rules;
- request payload validation;
- presenters and typed view models;
- authentication and authorization flows;
- rate limiting;
- ownership isolation;
- access request notification dispatch and handling.

Run backend tests with:

```bash
docker compose exec tracker-api-php ./vendor/bin/phpunit
```

Run frontend tests with:

```bash
cd TrackerApp
npm test
```

## Production-Like Aspects

This remains a personal project, but it is intentionally shaped like code that could be reviewed in a production team:

- explicit module boundaries;
- domain model separated from persistence records;
- value objects for meaningful business concepts;
- framework adapters kept at the edges;
- async worker for access request notifications;
- JSON API error handling;
- guarded admin and authenticated routes;
- Dockerized runtime with reproducible services;
- test coverage for business rules and API-facing behavior.

