#!/usr/bin/env bash

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}

WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress/}
WP_CORE_DIR="${WP_CORE_DIR%/}/"

download() {
	if command -v curl >/dev/null 2>&1; then
		curl -fsSL "$1" -o "$2"
	elif command -v wget >/dev/null 2>&1; then
		wget -q -O "$2" "$1"
	else
		echo "Neither curl nor wget is available."
		exit 1
	fi
}

set -ex

get_installed_wp_version() {
	local wp_version_file="$WP_CORE_DIR/wp-includes/version.php"

	if [ ! -f "$wp_version_file" ]; then
		echo ""
		return
	fi

	php -r "require '$wp_version_file'; echo \$wp_version;" 2>/dev/null || true
}

resolve_wp_tests_ref() {
	if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
		echo "trunk"
		return
	fi

	if [[ $WP_VERSION == 'latest' ]]; then
		local installed_version
		installed_version="$(get_installed_wp_version)"
		if [[ -n "$installed_version" ]]; then
			echo "$installed_version"
			return
		fi

		# http serves a single offer, whereas https serves multiple. we only want one
		download http://api.wordpress.org/core/version-check/1.7/ /tmp/wp-latest.json
		local latest_version
		latest_version=$(grep -o '"version":"[^"]*' /tmp/wp-latest.json | sed 's/"version":"//')
		if [[ -z "$latest_version" ]]; then
			echo "Latest WordPress version could not be found"
			exit 1
		fi
		echo "$latest_version"
		return
	fi

	echo "$WP_VERSION"
}

download_wordpress_develop_archive() {
	local ref="$1"
	local archive="$2"
	local tag_url="https://codeload.github.com/WordPress/wordpress-develop/tar.gz/refs/tags/$ref"
	local branch_url="https://codeload.github.com/WordPress/wordpress-develop/tar.gz/refs/heads/$ref"

	download "$tag_url" "$archive" || true
	if tar -tzf "$archive" >/dev/null 2>&1; then
		return
	fi

	download "$branch_url" "$archive" || true
	if tar -tzf "$archive" >/dev/null 2>&1; then
		return
	fi

	echo "Could not download WordPress test suite archive for ref '$ref'"
	exit 1
}

install_wp() {

	if [ -f "$WP_CORE_DIR/wp-includes/version.php" ]; then
		return;
	fi

	mkdir -p "$WP_CORE_DIR"

	if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
		mkdir -p /tmp/wordpress-nightly
		download https://wordpress.org/nightly-builds/wordpress-latest.zip  /tmp/wordpress-nightly/wordpress-nightly.zip
		unzip -q /tmp/wordpress-nightly/wordpress-nightly.zip -d /tmp/wordpress-nightly/
		mv /tmp/wordpress-nightly/wordpress/* "$WP_CORE_DIR"
	else
		if [ $WP_VERSION == 'latest' ]; then
			local ARCHIVE_NAME='latest'
		else
			local ARCHIVE_NAME="wordpress-$WP_VERSION"
		fi
		download "https://wordpress.org/${ARCHIVE_NAME}.tar.gz" /tmp/wordpress.tar.gz
		tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C "$WP_CORE_DIR"
	fi

	download https://raw.github.com/markoheijnen/wp-mysqli/master/db.php "$WP_CORE_DIR/wp-content/db.php"
}

install_test_suite() {
	local wp_tests_ref
	local wp_tests_archive
	local wp_tests_source

	# portable in-place argument for both GNU sed and Mac OSX sed
	if [[ $(uname -s) == 'Darwin' ]]; then
		local ioption='-i .bak'
	else
		local ioption='-i'
	fi

	wp_tests_ref="$(resolve_wp_tests_ref)"
	wp_tests_archive="/tmp/wordpress-develop-${wp_tests_ref}.tar.gz"
	wp_tests_source="/tmp/wordpress-develop-${wp_tests_ref}"

	if [ ! -f "$WP_TESTS_DIR/includes/functions.php" ] || [ ! -f "$WP_TESTS_DIR/wp-tests-config.php" ]; then
		mkdir -p "$WP_TESTS_DIR"

		rm -rf "$wp_tests_source"
		download_wordpress_develop_archive "$wp_tests_ref" "$wp_tests_archive"

		mkdir -p "$wp_tests_source"
		tar --strip-components=1 -zxmf "$wp_tests_archive" -C "$wp_tests_source"
	fi

	if [ ! -f "$WP_TESTS_DIR/includes/functions.php" ]; then
		cp -R "$wp_tests_source/tests/phpunit/includes" "$WP_TESTS_DIR/includes"
	fi

	if [ ! -f "$WP_TESTS_DIR/data/formatting/entities.txt" ]; then
		cp -R "$wp_tests_source/tests/phpunit/data" "$WP_TESTS_DIR/data"
	fi

	if [ ! -f "$WP_TESTS_DIR/wp-tests-config.php" ]; then
		cp "$wp_tests_source/wp-tests-config-sample.php" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR"/wp-tests-config.php
		sed $ioption "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR"/wp-tests-config.php
	fi

}

install_db() {
	# parse DB_HOST for port or socket references
	local PARTS=(${DB_HOST//\:/ })
	local DB_HOSTNAME=${PARTS[0]};
	local DB_SOCK_OR_PORT=${PARTS[1]};
	local EXTRA=""

	if ! [ -z $DB_HOSTNAME ] ; then
		if [ $(echo $DB_SOCK_OR_PORT | grep -e '^[0-9]\{1,\}$') ]; then
			EXTRA=" --host=$DB_HOSTNAME --port=$DB_SOCK_OR_PORT --protocol=tcp"
		elif ! [ -z $DB_SOCK_OR_PORT ] ; then
			EXTRA=" --socket=$DB_SOCK_OR_PORT"
		elif ! [ -z $DB_HOSTNAME ] ; then
			EXTRA=" --host=$DB_HOSTNAME --protocol=tcp"
		fi
	fi

	# create database if needed
	mysql --user="$DB_USER" --password="$DB_PASS"$EXTRA --execute="CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;"
}

install_wp
install_test_suite
install_db
