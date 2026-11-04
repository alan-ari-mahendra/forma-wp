#!/usr/bin/env bash
# Deploy FORMA custom code (plugin forma-core + child theme hello-elementor-child) to the VPS.
# Used by GitHub Actions and for manual deploys:
#   DEPLOY_HOST=46.250.232.141 DEPLOY_USER=deploy SSH_OPTS="-i ~/.ssh/ember-oak-deploy" ./deploy/deploy.sh
# Backs up both folders, rsyncs, flushes caches, runs a health check, and rolls back on failure.
set -euo pipefail
trim() { printf '%s' "$1" | tr -d '\r\n' | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//'; }
HOST="$(trim "${DEPLOY_HOST:?DEPLOY_HOST required}")"
DUSER="$(trim "${DEPLOY_USER:-deploy}")"; DUSER="${DUSER:-deploy}"
SITE_URL="$(trim "${SITE_URL:-}")"; SITE_URL="${SITE_URL:-http://$HOST}"
REMOTE_ROOT="$(trim "${REMOTE_ROOT:-}")"; REMOTE_ROOT="${REMOTE_ROOT:-/var/www/forma}"
SITE_PORT="$(trim "${SITE_PORT:-}")"
PLUGIN_DIR="$(trim "${PLUGIN_DIR:-}")"; PLUGIN_DIR="${PLUGIN_DIR:-forma-core}"
THEME_DIR="$(trim "${THEME_DIR:-}")"; THEME_DIR="${THEME_DIR:-hello-elementor-child}"
[[ "$REMOTE_ROOT" =~ ^/[A-Za-z0-9._/-]+$ && "$REMOTE_ROOT" != *..* ]] || { echo "::error::REMOTE_ROOT must be an absolute path like /var/www/forma (got '${REMOTE_ROOT}')"; exit 2; }
[[ "$PLUGIN_DIR" =~ ^[A-Za-z0-9._-]+$ && "$THEME_DIR" =~ ^[A-Za-z0-9._-]+$ ]] || { echo "::error::PLUGIN_DIR/THEME_DIR must be plain folder names"; exit 2; }
[[ "$HOST" =~ ^[A-Za-z0-9.-]+$ && "$DUSER" =~ ^[a-z_][a-z0-9_-]*$ ]] || { echo "::error::bad DEPLOY_HOST/DEPLOY_USER"; exit 2; }
PROJECT="${PROJECT:-$(basename "$REMOTE_ROOT")}"
SSH_OPTS="${SSH_OPTS:-}"
HOSTPORT="$(printf '%s' "$SITE_URL" | sed -E 's#^https?://##; s#/.*$##')"
SITE_HOST="${HOSTPORT%%:*}"
if [ -z "$SITE_PORT" ]; then
  case "$HOSTPORT" in *:*) SITE_PORT="${HOSTPORT##*:}";; *) SITE_PORT=$([[ "$SITE_URL" == https://* ]] && echo 443 || echo 80);; esac
fi
[[ "$SITE_PORT" =~ ^[0-9]+$ ]] || { echo "bad SITE_PORT: $SITE_PORT"; exit 1; }
SCHEME=http; [ "$SITE_PORT" = "443" ] && SCHEME=https

# component = local path (relative to repo root) under wp-content
COMPONENTS=("plugins/$PLUGIN_DIR" "themes/$THEME_DIR")
WPC="$REMOTE_ROOT/wp-content"
SHA="${GITHUB_SHA:-$(git rev-parse HEAD 2>/dev/null || echo manual)}"
BK="/var/backups/$PROJECT/forma-code-$(date +%Y%m%d%H%M%S)-${SHA:0:7}.tgz"
WPUSER="${WP_USER:-deploy}"
if [ "$DUSER" = root ]; then WPU="sudo -u $WPUSER "; CHOWN="chown -R $WPUSER:www-data"; else WPU=""; CHOWN="true"; fi
R() { ssh $SSH_OPTS -o BatchMode=yes "$DUSER@$HOST" "$@"; }

for c in "${COMPONENTS[@]}"; do [ -d "wp-content/$c" ] || { echo "run from repo root (wp-content/$c missing)"; exit 1; }; done

echo "==> pre-flight: $REMOTE_ROOT must be a WordPress install"
R "test -f '$REMOTE_ROOT/wp-config.php' && test -d '$WPC/plugins'" \
  || { echo "::error::$REMOTE_ROOT on the server is not a WordPress root (no wp-config.php). Check REMOTE_ROOT, or run deploy/migrate-once.sh first."; exit 3; }

echo "==> pre-flight: nginx must listen on port $SITE_PORT for $PROJECT"
if ! R "ss -tln | awk '{print \$4}' | grep -qE '[:.]$SITE_PORT\$'"; then
  ACTUAL="$(R "awk '/^[[:space:]]*listen/' /etc/nginx/sites-enabled/$PROJECT 2>/dev/null | tr -cs '0-9' ' '" || true)"
  echo "::error::SITE_PORT=$SITE_PORT does not match the server: nothing listens on $SITE_PORT. Vhost '$PROJECT' listens on: ${ACTUAL:-unknown}."
  exit 4
fi

echo "==> backup current code"
R "mkdir -p '/var/backups/$PROJECT' '$WPC/plugins/$PLUGIN_DIR' '$WPC/themes/$THEME_DIR' && tar -czf '$BK' -C '$WPC' 'plugins/$PLUGIN_DIR' 'themes/$THEME_DIR' && echo saved $BK"

echo "==> rsync"
for c in "${COMPONENTS[@]}"; do
  rsync -az --delete --no-owner --no-group --chmod=Du=rwx,Dgo=rx,Fu=rw,Fgo=r \
    --exclude='.DS_Store' --exclude='*.log' -e "ssh $SSH_OPTS -o BatchMode=yes" "wp-content/$c/" "$DUSER@$HOST:$WPC/$c/"
  R "$CHOWN '$WPC/$c'"
done

post_deploy() {
  R "cd '$REMOTE_ROOT' && ${WPU}wp cache flush >/dev/null 2>&1 || true; ${WPU}wp elementor flush-css >/dev/null 2>&1 || true; $([ "$DUSER" = root ] && echo systemctl || echo sudo -n /usr/bin/systemctl) reload php8.3-fpm"
}
health() {
  local HOSTHDR="$SITE_HOST"; case "$SITE_PORT" in 80|443) ;; *) HOSTHDR="$SITE_HOST:$SITE_PORT";; esac
  local base="$SCHEME://127.0.0.1:$SITE_PORT"; local k=""; [ "$SCHEME" = https ] && k="-k"
  R "set -e; H=\$(curl -fsS $k -m 25 -H 'Host: $HOSTHDR' $base${HEALTH_PATH:-/}); echo \"\$H\" | grep -q '${HEALTH_MARKER:-hero-title}'; \
     echo \"\$H\" | grep -q 'ExerciseGym'; \
     curl -fsS $k -m 25 -o /dev/null -H 'Host: $HOSTHDR' $base/wp-json/"
}

echo "==> post-deploy"; post_deploy
echo "==> health check"
if health; then
  echo "HEALTH_OK"
  R "ls -1t /var/backups/$PROJECT/forma-code-*.tgz 2>/dev/null | tail -n +6 | xargs -r rm -f"
else
  echo "HEALTH_FAILED -> rolling back to $BK" >&2
  R "rm -rf '$WPC/plugins/$PLUGIN_DIR' '$WPC/themes/$THEME_DIR' && tar -xzf '$BK' -C '$WPC' && $CHOWN '$WPC/plugins/$PLUGIN_DIR' && $CHOWN '$WPC/themes/$THEME_DIR'"
  post_deploy || true
  exit 1
fi
