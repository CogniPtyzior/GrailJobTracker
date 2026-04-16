#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="/app/grailjobtracker"
TRACKER_APP_DIR="$ROOT_DIR/TrackerApp"
TRACKER_API_DIR="$ROOT_DIR/TrackerApi"
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

[[ -d "$ROOT_DIR" ]] || abort "Répertoire introuvable: $ROOT_DIR"
[[ -d "$TRACKER_APP_DIR/.git" ]] || abort "Dépôt git introuvable: $TRACKER_APP_DIR"
[[ -d "$TRACKER_API_DIR/.git" ]] || abort "Dépôt git introuvable: $TRACKER_API_DIR"
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

pull_repo() {
  local repo_dir="$1"

  log "Contrôle de l'état git: $repo_dir"

  if [[ -n "$(git -C "$repo_dir" status --porcelain)" ]]; then
    abort "Le dépôt $repo_dir contient des modifications locales non commit. Abandon."
  fi

  local branch
  branch="$(git -C "$repo_dir" rev-parse --abbrev-ref HEAD)"
  [[ "$branch" != "HEAD" ]] || abort "Le dépôt $repo_dir est en detached HEAD. Abandon."

  log "git fetch origin $branch"
  git -C "$repo_dir" fetch origin "$branch"

  local local_sha remote_sha base_sha
  local_sha="$(git -C "$repo_dir" rev-parse HEAD)"
  remote_sha="$(git -C "$repo_dir" rev-parse FETCH_HEAD)"
  base_sha="$(git -C "$repo_dir" merge-base HEAD FETCH_HEAD)"

  if [[ "$local_sha" = "$remote_sha" ]]; then
    log "Aucune mise à jour distante pour $repo_dir"
    return 0
  fi

  if [[ "$local_sha" != "$base_sha" ]]; then
    abort "Le dépôt $repo_dir diverge du remote. Aucun forçage n'est appliqué."
  fi

  log "git pull --ff-only origin $branch"
  git -C "$repo_dir" pull --ff-only origin "$branch"
}

log "===== Mise à jour Git de TrackerApp ====="
pull_repo "$TRACKER_APP_DIR"

log "===== Mise à jour Git de TrackerApi ====="
pull_repo "$TRACKER_API_DIR"

log "===== npm install dans TrackerApp ====="
cd "$TRACKER_APP_DIR"
npm install

log "===== npm run build dans TrackerApp ====="
npm run build

log "===== Rebuild Docker de la nouvelle application ====="
cd "$ROOT_DIR"
docker compose -f compose.yaml up -d --build

log "===== État de la stack ====="
docker compose -f compose.yaml ps

log "===== Terminé ====="
echo "Application reconstruite avec succès."