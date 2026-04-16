#!/bin/sh
set -eu

APP_DIR="/var/www/tracker-api"
WAIT_FOR_DB_TIMEOUT="${WAIT_FOR_DB_TIMEOUT:-60}"
AUTO_COMPOSER_INSTALL="${AUTO_COMPOSER_INSTALL:-1}"
COMPOSER_INSTALL_FLAGS="${COMPOSER_INSTALL_FLAGS:---prefer-dist --no-interaction}"
AUTO_INIT_DB="${AUTO_INIT_DB:-1}"
AUTO_MIGRATE="${AUTO_MIGRATE:-0}"
AUTO_BOOTSTRAP_ADMIN="${AUTO_BOOTSTRAP_ADMIN:-1}"
XDEBUG_RUNTIME_INI="/usr/local/etc/php/conf.d/zzz-xdebug-runtime.ini"

log() {
  printf '%s\n' "[tracker-api] $*"
}

urlencode() {
  php -r 'echo rawurlencode($argv[1]);' "$1"
}

configure_mailer() {
  if [ -n "${MAILER_DSN:-}" ]; then
    return 0
  fi

  if [ -z "${SMTP_HOST:-}" ] || [ -z "${SMTP_PORT:-}" ] || [ -z "${SMTP_USERNAME:-}" ] || [ -z "${SMTP_PASSWORD_FILE:-}" ]; then
    return 0
  fi

  if [ ! -f "$SMTP_PASSWORD_FILE" ]; then
    log "SMTP password file not found: ${SMTP_PASSWORD_FILE}"
    exit 1
  fi

  SMTP_PASSWORD="$(tr -d '\r\n' < "$SMTP_PASSWORD_FILE")"

  if [ -z "$SMTP_PASSWORD" ]; then
    log "SMTP password file is empty: ${SMTP_PASSWORD_FILE}"
    exit 1
  fi

  SMTP_USERNAME_ENCODED="$(urlencode "$SMTP_USERNAME")"
  SMTP_PASSWORD_ENCODED="$(urlencode "$SMTP_PASSWORD")"

  if [ "${SMTP_USE_STARTTLS:-0}" = "1" ]; then
    export MAILER_DSN="smtp://${SMTP_USERNAME_ENCODED}:${SMTP_PASSWORD_ENCODED}@${SMTP_HOST}:${SMTP_PORT}?encryption=tls"
  else
    export MAILER_DSN="smtps://${SMTP_USERNAME_ENCODED}:${SMTP_PASSWORD_ENCODED}@${SMTP_HOST}:${SMTP_PORT}"
  fi
}

configure_xdebug() {
  if [ -z "${XDEBUG_CLIENT_HOST:-}" ]; then
    rm -f "$XDEBUG_RUNTIME_INI"
    return 0
  fi

  cat > "$XDEBUG_RUNTIME_INI" <<EOF
xdebug.client_host=${XDEBUG_CLIENT_HOST}
EOF
}

get_database_value() {
  key="$1"

  php -r '
    $key = $argv[1];
    $url = getenv("DATABASE_URL") ?: "";
    if ($url === "") {
        exit(1);
    }

    $parts = parse_url($url);
    if ($parts === false) {
        exit(2);
    }

    parse_str($parts["query"] ?? "", $query);

    $values = [
        "host" => $parts["host"] ?? "",
        "port" => (string)($parts["port"] ?? 5432),
        "user" => $parts["user"] ?? "",
        "pass" => $parts["pass"] ?? "",
        "dbname" => ltrim($parts["path"] ?? "", "/"),
        "schema" => $query["currentSchema"] ?? getenv("TRACKER_SCHEMA") ?: "public",
    ];

    echo $values[$key] ?? "";
  ' "$key"
}

command_exists() {
  command_name="$1"
  php bin/console "$command_name" --help >/dev/null 2>&1
}

needs_composer_install() {
  if [ ! -f composer.json ]; then
    return 1
  fi

  if [ ! -f vendor/autoload.php ]; then
    return 0
  fi

  if [ -f composer.lock ] && [ composer.lock -nt vendor/autoload.php ]; then
    return 0
  fi

  return 1
}

install_dependencies() {
  if [ "$AUTO_COMPOSER_INSTALL" != "1" ]; then
    log "Automatic Composer install is disabled."
    return 0
  fi

  if ! command -v composer >/dev/null 2>&1; then
    log "Composer is not available in the container."
    exit 1
  fi

  if needs_composer_install; then
    log "Installing Composer dependencies..."
    # shellcheck disable=SC2086
    composer install $COMPOSER_INSTALL_FLAGS
    log "Composer dependencies installed."
  else
    log "Composer dependencies are already up to date."
  fi
}

wait_for_database() {
  DB_HOST="$(get_database_value host)"
  DB_PORT="$(get_database_value port)"
  DB_NAME="$(get_database_value dbname)"
  DB_USER="$(get_database_value user)"
  DB_PASS="$(get_database_value pass)"

  if [ -z "$DB_HOST" ] || [ -z "$DB_NAME" ] || [ -z "$DB_USER" ]; then
    log "Skipping database wait because DATABASE_URL is incomplete."
    return 0
  fi

  elapsed=0

  log "Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT}/${DB_NAME}..."

  until PGPASSWORD="$DB_PASS" pg_isready -h "$DB_HOST" -p "$DB_PORT" -d "$DB_NAME" -U "$DB_USER" >/dev/null 2>&1; do
    if [ "$elapsed" -ge "$WAIT_FOR_DB_TIMEOUT" ]; then
      log "Database did not become ready within ${WAIT_FOR_DB_TIMEOUT} seconds."
      exit 1
    fi

    sleep 2
    elapsed=$((elapsed + 2))
  done

  log "PostgreSQL is ready."
}

init_schema() {
  SCHEMA_NAME="$(get_database_value schema)"

  case "$SCHEMA_NAME" in
    ''|*[!A-Za-z0-9_]*)
      log "Invalid schema name: ${SCHEMA_NAME}"
      exit 1
      ;;
  esac

  if [ "$AUTO_INIT_DB" != "1" ]; then
    log "Schema initialization is disabled."
    return 0
  fi

  if [ ! -f "$APP_DIR/bin/console" ] || [ ! -f "$APP_DIR/vendor/autoload.php" ]; then
    log "Skipping schema initialization because the Symfony application is not fully installed yet."
    return 0
  fi

  if ! command_exists "dbal:run-sql"; then
    log "Skipping schema initialization because dbal:run-sql is not available yet."
    return 0
  fi

  log "Ensuring schema '${SCHEMA_NAME}' exists..."
  php bin/console dbal:run-sql "CREATE SCHEMA IF NOT EXISTS ${SCHEMA_NAME}"
  log "Schema check completed."
}

run_migrations() {
  if [ "$AUTO_MIGRATE" != "1" ]; then
    log "Automatic migrations are disabled."
    return 0
  fi

  if [ ! -f "$APP_DIR/bin/console" ] || [ ! -f "$APP_DIR/vendor/autoload.php" ]; then
    log "Skipping migrations because the Symfony application is not fully installed yet."
    return 0
  fi

  if ! command_exists "doctrine:migrations:migrate"; then
    log "Skipping migrations because doctrine:migrations:migrate is not available yet."
    return 0
  fi

  log "Running Doctrine migrations..."
  php bin/console doctrine:migrations:migrate --no-interaction
  log "Doctrine migrations completed."
}

bootstrap_admin() {
  if [ "$AUTO_BOOTSTRAP_ADMIN" != "1" ]; then
    log "Bootstrap admin creation is disabled."
    return 0
  fi

  if [ ! -f "$APP_DIR/bin/console" ] || [ ! -f "$APP_DIR/vendor/autoload.php" ]; then
    log "Skipping bootstrap admin because the Symfony application is not fully installed yet."
    return 0
  fi

  if ! command_exists "app:bootstrap-admin"; then
    log "Skipping bootstrap admin because app:bootstrap-admin is not available yet."
    return 0
  fi

  log "Bootstrapping admin if needed..."
  php bin/console app:bootstrap-admin
  log "Bootstrap admin completed."
}

cd "$APP_DIR"

configure_mailer
configure_xdebug
install_dependencies
wait_for_database
init_schema
run_migrations
bootstrap_admin

echo "[tracker-api] Preparing PHP session directory..."
mkdir -p /tmp/sessions
chown -R www-data:www-data /tmp/sessions
chmod 1733 /tmp/sessions

log "Starting PHP-FPM..."
exec php-fpm -F
