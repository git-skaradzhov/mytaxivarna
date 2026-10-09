#!/bin/sh
# Install local WordPress and GeneratePress, then activate the MyTaxi child theme.
set -eu

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
	echo "Missing .env. Copy .env.example and set local values first." >&2
	exit 1
fi

if [ ! -f wordpress/wp-load.php ] || [ ! -d wordpress/wp-admin ] || [ ! -d wordpress/wp-includes ]; then
	echo "Missing the standard WordPress directories in wordpress/." >&2
	exit 1
fi

if [ ! -f generatepress.3.6.1.zip ]; then
	echo "Missing generatepress.3.6.1.zip" >&2
	exit 1
fi

docker compose up -d

echo "Waiting for WordPress configuration..."
i=0
until docker compose exec -T wordpress test -f /var/www/html/wp-config.php; do
	i=$((i + 1))
	if [ "$i" -gt 60 ]; then
		echo "Timed out waiting for wp-config.php" >&2
		exit 1
	fi
	sleep 2
done

set -a
# shellcheck disable=SC1091
. ./.env
set +a

: "${WP_URL:?}"
: "${WP_TITLE:?}"
: "${WP_ADMIN_USER:?}"
: "${WP_ADMIN_PASSWORD:?}"
: "${WP_ADMIN_EMAIL:?}"

if ! docker compose --profile cli run --rm wpcli wp core is-installed; then
	docker compose --profile cli run --rm wpcli wp core install \
		--url="$WP_URL" \
		--title="$WP_TITLE" \
		--admin_user="$WP_ADMIN_USER" \
		--admin_password="$WP_ADMIN_PASSWORD" \
		--admin_email="$WP_ADMIN_EMAIL" \
		--skip-email
fi

docker compose --profile cli run --rm wpcli wp theme install /theme/generatepress.zip --force
docker compose --profile cli run --rm wpcli wp theme activate mytaxi-generatepress
docker compose --profile cli run --rm wpcli wp plugin activate mytaxi-core
docker compose --profile cli run --rm wpcli wp mytaxi setup
docker compose --profile cli run --rm wpcli wp option update blog_public 0
docker compose --profile cli run --rm wpcli wp rewrite structure '/%postname%/' --hard

echo "Installed themes:"
docker compose --profile cli run --rm wpcli wp theme list
