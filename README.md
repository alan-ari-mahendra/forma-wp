# Deploy FORMA (VPS Brata)

- **Situs:** http://46.250.232.141:8634
- **Repo:** `alan-ari-mahendra/forma-wp` (branch `main`)
- **Server:** sama dengan Ember & Oak (`46.250.232.141`), project `forma`: folder `/var/www/forma`, pool PHP-FPM `forma`, DB `forma`, vhost nginx port **8634**.

Pola, secrets, key CI, rollback, dan troubleshooting identik dengan `ember-oak/deploy/README.md`. Bedanya:

- CI men-deploy **dua folder**: plugin `wp-content/plugins/forma-core/` dan child theme `wp-content/themes/hello-elementor-child/`.
  Elementor, Pro Elements, SCF, Rank Math, WooCommerce, uploads, dan database ada di server dan tidak disentuh CI.
- Health check: halaman `/` harus berisi `hero-title` dan `ExerciseGym` (JSON-LD), dan `/wp-json/` harus 200.
- Backup: `/var/backups/forma/forma-code-*.tgz` (5 terakhir).
- Secrets (repo `forma-wp` → Settings → Secrets): `SSH_PRIVATE_KEY`, `SSH_HOST=46.250.232.141`, `SSH_USER=root`, `SSH_KNOWN_HOSTS`,
  `SITE_URL=http://46.250.232.141:8634`, `SITE_PORT=8634`, `REMOTE_ROOT=/var/www/forma`; opsional `PLUGIN_DIR`, `THEME_DIR`. Buat environment `production`.

## Setup awal (sekali)
```fish
# 1) provisioning server
ssh sukun-vps-brata "DEPLOY_PUBKEY='"(cat ~/.ssh/ember-oak-deploy.pub)"' PROJECT=forma SITE_PORT=8634 bash -s" < deploy/server-setup.sh
# 2) migrasi konten (DB, uploads, plugin pihak ketiga, tema, wp-config) + deploy pertama
env PROJECT=forma SITE_PORT=8634 OLD_URL=http://forma.test ./deploy/migrate-once.sh
```
Setelah itu, `git push origin main` yang mengubah plugin/tema memicu deploy otomatis.

## Manual
```fish
env DEPLOY_HOST=46.250.232.141 DEPLOY_USER=root SITE_URL=http://46.250.232.141:8634 SITE_PORT=8634 REMOTE_ROOT=/var/www/forma \
    SSH_OPTS="-i $HOME/.ssh/ember-oak-ci-root -o IdentitiesOnly=yes" ./deploy/deploy.sh
```
