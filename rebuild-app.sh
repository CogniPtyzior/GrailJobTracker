#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="/app/grailjobtracker"
TRACKER_APP_DIR="$ROOT_DIR/TrackerApp"
GITHUB_USERNAME="CogniPtyzior"

log() {
  printf '\n[%s] %s\n' "$(date '+%F %T')" "$*"
}

abort() {
  echo "ERREUR: $*" >&2
  exit 1
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || abort "Commande introuvable: $1"
}

require_cmd git
require_cmd npm
require_cmd docker

[[ -d "$ROOT_DIR/.git" ]] || abort "Dépôt git introuvable: $ROOT_DIR"
[[ -d "$TRACKER_APP_DIR" ]] || abort "Répertoire introuvable: $TRACKER_APP_DIR"
[[ -f "$ROOT_DIR/compose.yaml" ]] || abort "Fichier compose.yaml introuvable"
[[ -f "$ROOT_DIR/.env.docker.local" ]] || abort "Fichier .env.docker.local introuvable"
[[ -f "$ROOT_DIR/credentials/password.secret" ]] || abort "Secret admin manquant"
[[ -f "$ROOT_DIR/credentials/smtp/password.secret" ]] || abort "Secret SMTP manquant"

read -rsp "Token GitHub pour ${GITHUB_USERNAME}: " GITHUB_TOKEN
echo
[[ -n "${GITHUB_TOKEN}" ]] || abort "Token GitHub vide"

ASKPASS_FILE="$(mktemp)"

cleanup() {
  rm -f "$ASKPASS_FILE"
  unset GITHUB_TOKEN || true
}
trap cleanup EXIT

cat > "$ASKPASS_FILE" <<'EOF'
#!/usr/bin/env bash
case "$1" in
  *Username* ) printf '%s\n' "${GITHUB_USERNAME}" ;;
  *Password* ) printf '%s\n' "${GITHUB_TOKEN}" ;;
  * ) printf '\n' ;;
esac
EOF

chmod 700 "$ASKPASS_FILE"

export GITHUB_USERNAME
export GITHUB_TOKEN
export GIT_ASKPASS="$ASKPASS_FILE"
export GIT_TERMINAL_PROMPT=0

log "===== Contrôle de l'état git du dépôt racine ====="

if [[ -n "$(git -C "$ROOT_DIR" status --porcelain)" ]]; then
  abort "Le dépôt $ROOT_DIR contient des modifications locales non commit. Abandon."
fi

BRANCH="$(git -C "$ROOT_DIR" rev-parse --abbrev-ref HEAD)"
[[ "$BRANCH" != "HEAD" ]] || abort "Le dépôt $ROOT_DIR est en detached HEAD. Abandon."

log "git fetch origin $BRANCH"
git -C "$ROOT_DIR" fetch origin "$BRANCH"

LOCAL_SHA="$(git -C "$ROOT_DIR" rev-parse HEAD)"
REMOTE_SHA="$(git -C "$ROOT_DIR" rev-parse FETCH_HEAD)"
BASE_SHA="$(git -C "$ROOT_DIR" merge-base HEAD FETCH_HEAD)"

if [[ "$LOCAL_SHA" = "$REMOTE_SHA" ]]; then
  log "Aucune mise à jour distante sur $BRANCH"
else
  if [[ "$LOCAL_SHA" != "$BASE_SHA" ]]; then
    abort "Le dépôt $ROOT_DIR diverge du remote. Aucun forçage n'est appliqué."
  fi

  log "git pull --ff-only origin $BRANCH"
  git -C "$ROOT_DIR" pull --ff-only origin "$BRANCH"
fi

log "===== npm install dans TrackerApp ====="
cd "$TRACKER_APP_DIR"
npm install

log "===== npm run build dans TrackerApp ====="
npm run build

log "===== Rebuild Docker de la nouvelle application ====="
cd "$ROOT_DIR"
docker compose -f compose.production.yaml up -d --build --force-recreate

log "===== État de la stack ====="
docker compose -f compose.yaml ps

log "===== Terminé ====="
echo "Application reconstruite avec succès."