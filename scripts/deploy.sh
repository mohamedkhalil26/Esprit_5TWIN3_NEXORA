#!/usr/bin/env bash
set -Eeuo pipefail

# Exécuté uniquement par déclenchement manuel du workflow CD.
root="${1:?Répertoire de déploiement requis}"
release_id="${2:?Identifiant requis}"
health_url="${3:?URL de contrôle requise}"
[[ "$root" =~ ^/srv/[a-zA-Z0-9_/-]+$ && "$root" != *..* ]]
[[ "$release_id" =~ ^[0-9]+-[0-9]+$ ]]
[[ "$health_url" =~ ^https://[a-zA-Z0-9./_-]+$ ]]
test -f "$root/shared/.env"
test -x "$root/shared/backup-before-deploy"
test -d "$root/shared/storage"
test -d "$root/shared/uploads"

exec 9>"$root/.deploy.lock"
flock -n 9
# Le programme doit sauvegarder MySQL ET les fichiers partagés et échouer si la sauvegarde échoue.
"$root/shared/backup-before-deploy"
release="$root/releases/$release_id"
mkdir -p "$root/releases"
mkdir "$release"
tar -xzf "/tmp/solarshare-$release_id.tar.gz" -C "$release"
ln -s "$root/shared/.env" "$release/.env"
ln -s "$root/shared/storage" "$release/storage"
ln -s "$root/shared/uploads" "$release/public/uploads"
mkdir -p "$release/bootstrap/cache"
cd "$release"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan storage:link
php artisan migrate --force --isolated=75
php artisan optimize

previous="$(readlink -f "$root/current" || true)"
rollback_code() {
    if [[ -n "$previous" && -d "$previous" ]]; then
        ln -s "$previous" "$root/current-rollback-$release_id"
        mv -Tf "$root/current-rollback-$release_id" "$root/current"
    fi
    echo 'Échec de contrôle : code précédent restauré si disponible. Les migrations ne sont pas annulées automatiquement.' >&2
}
ln -s "$release" "$root/current-next-$release_id"
mv -Tf "$root/current-next-$release_id" "$root/current"
trap rollback_code ERR
curl --fail --silent --show-error --retry 5 --retry-delay 3 "$health_url/up" > /dev/null
curl --fail --silent --show-error --retry 3 "$health_url/" > /dev/null
php artisan queue:restart
trap - ERR
echo "Livraison $release_id active. Vérifier aussi les parcours connectés et les emails."
