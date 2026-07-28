# GrailJobTracker

GrailJobTracker is a full-stack web application designed to centralize and manage job applications during an active job search.

The project provides a private dashboard to track companies, roles, application status, follow-up dates, interviews, contacts, notes, remote preferences and other useful information throughout a recruitment process.

## Overview

The application is split into two main parts:

- `TrackerApi` — Symfony backend exposing the API, authentication, admin features and persistence layer.
- `TrackerApp` — Angular frontend providing the user interface.
- `docker` — Docker configuration for PHP-FPM and Nginx.
- `compose.yaml` — local Docker stack for the backend API.
- `compose.production.yaml` — production-oriented Docker stack.

## Features

### Candidate tracking

Users can create, update, search and manage tracked job applications with fields such as:

- Company name
- Job title
- Contract type
- Location
- Remote mode
- Remuneration
- Offer URL
- Notes
- Application date
- Planned follow-up date
- Effective follow-up date
- Interview dates
- HR and business contacts
- Subjective relevance score
- Application status

Supported statuses include draft, applied, follow-up pending, first contact, interviews, offer received, hired, rejected and withdrawn.

### Filtering and search

The application includes filters for:

- Search text
- Company
- Status
- Contract type
- Remote mode

It also supports company suggestions and CSV export.

### Authentication

The application includes a login system backed by the Symfony API.

Frontend routes are protected with authentication guards, and admin-only routes are protected with a dedicated admin guard.

### Admin area

The admin area allows privileged users to manage:

- Users
- Access requests
- Account activation
- Admin roles

### Access request flow

Visitors can submit an access request with:

- Email
- Company name
- Reason
- Optional first name and last name

Admins can review, approve or delete access requests.

## Tech stack

### Backend

- PHP 8.4
- Symfony 7.4
- Doctrine ORM / DBAL
- Doctrine Migrations
- Symfony Security
- Symfony Mailer
- Symfony Serializer
- Symfony Validator
- Symfony Messenger (for Access Requests)
- Twig
- Nelmio API Doc
- PostgreSQL

### Frontend

- Angular 20
- Angular Material
- TypeScript
- RxJS
- SCSS

### Infrastructure

- Docker Compose
- PHP-FPM
- Nginx
- PostgreSQL expected externally or through an existing Docker network
- Environment-based configuration
- Secret files for admin and SMTP passwords

## Project structure

```txt
GrailJobTracker/
├── TrackerApi/              # Symfony backend
│   ├── bin/
│   ├── config/
│   ├── migrations/
│   ├── public/
│   ├── src/
│   ├── templates/
│   ├── tests/
│   ├── composer.json
│   └── README.md
│
├── TrackerApp/              # Angular frontend
│   ├── public/
│   ├── src/
│   ├── angular.json
│   ├── package.json
│   └── tsconfig.json
│
├── docker/                  # Docker images and Nginx config
├── credentials/             # Local secret files, ignored except placeholders
├── compose.yaml             # Local Docker stack
├── compose.production.yaml  # Production Docker stack
├── .env.docker.sample       # Environment example
└── rebuild-app.sh           # Production rebuild helper
