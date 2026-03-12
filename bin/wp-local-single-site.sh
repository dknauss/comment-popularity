#!/usr/bin/env bash
set -euo pipefail

LOCAL_DOMAIN="${LOCAL_DOMAIN:-single-site-local.local}"
LOCAL_SITES_JSON="${LOCAL_SITES_JSON:-$HOME/Library/Application Support/Local/sites.json}"
WP_BIN="${WP_BIN:-wp}"

site_id="xc4I92uA5"
site_root="$HOME/Development/Local Sites/single-site-local"

if [[ -f "$LOCAL_SITES_JSON" ]]; then
	site_meta="$(
		LOCAL_DOMAIN="$LOCAL_DOMAIN" LOCAL_SITES_JSON="$LOCAL_SITES_JSON" php <<'PHP'
<?php
$domain = getenv( 'LOCAL_DOMAIN' );
$json_path = getenv( 'LOCAL_SITES_JSON' );

if ( ! is_string( $domain ) || '' === $domain || ! is_string( $json_path ) || ! is_file( $json_path ) ) {
	exit( 1 );
}

$sites = json_decode( (string) file_get_contents( $json_path ), true );

if ( ! is_array( $sites ) ) {
	exit( 1 );
}

foreach ( $sites as $site ) {
	if ( ! is_array( $site ) ) {
		continue;
	}

	if ( ( $site['domain'] ?? '' ) === $domain ) {
		echo ( $site['id'] ?? '' ) . PHP_EOL;
		echo ( $site['path'] ?? '' ) . PHP_EOL;
		exit( 0 );
	}
}

exit( 1 );
PHP
	)" || true

	if [[ -n "$site_meta" ]]; then
		maybe_site_id="$(printf '%s\n' "$site_meta" | sed -n '1p')"
		maybe_site_root="$(printf '%s\n' "$site_meta" | sed -n '2p')"

		if [[ -n "$maybe_site_id" ]]; then
			site_id="$maybe_site_id"
		fi

		if [[ -n "$maybe_site_root" ]]; then
			site_root="${maybe_site_root/#\~/$HOME}"
		fi
	fi
fi

WP_PATH="${LOCAL_WP_PATH:-$site_root/app/public}"
MYSQL_SOCKET="${LOCAL_MYSQL_SOCKET:-$HOME/Library/Application Support/Local/run/$site_id/mysql/mysqld.sock}"
DB_HOST_VALUE="localhost:${MYSQL_SOCKET}"

if ! command -v "$WP_BIN" >/dev/null 2>&1; then
	echo "wp-local-single-site: wp binary not found (${WP_BIN})." >&2
	exit 1
fi

if [[ ! -d "$WP_PATH" ]]; then
	echo "wp-local-single-site: WordPress path not found: $WP_PATH" >&2
	exit 1
fi

if [[ ! -S "$MYSQL_SOCKET" ]]; then
	echo "wp-local-single-site: MySQL socket not found: $MYSQL_SOCKET" >&2
	echo "Set LOCAL_MYSQL_SOCKET if your Local site uses a different socket path." >&2
	exit 1
fi

combined_file="$(mktemp)"

set +e
"$WP_BIN" --path="$WP_PATH" --exec="define('DB_HOST', '${DB_HOST_VALUE}');" "$@" >"$combined_file" 2>&1
status=$?
set -e

if [[ -s "$combined_file" ]]; then
	filtered_output="$(
		grep -v -E "Case statements followed by a semicolon|Constant DB_HOST already defined" "$combined_file" || true
	)"
	if [[ -n "$filtered_output" ]]; then
		if [[ $status -eq 0 ]]; then
			printf '%s\n' "$filtered_output"
		else
			printf '%s\n' "$filtered_output" >&2
		fi
	fi
fi

rm -f "$combined_file"
exit "$status"
