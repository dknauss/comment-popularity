#!/usr/bin/env bash
set -euo pipefail

db_name="${WP_TESTS_DB_NAME:-wp_tests}"
db_user="${WP_TESTS_DB_USER:-root}"
db_pass="${WP_TESTS_DB_PASS:-root}"
db_host="${WP_TESTS_DB_HOST:-127.0.0.1}"
db_port="${WP_TESTS_DB_PORT:-3306}"
wp_version="${WP_VERSION:-6.4}"

mysql \
	--user="${db_user}" \
	--password="${db_pass}" \
	--host="${db_host}" \
	--port="${db_port}" \
	--protocol=tcp \
	--execute="DROP DATABASE IF EXISTS \`${db_name}\`; CREATE DATABASE \`${db_name}\`;"

bash bin/install-wp-tests.sh "${db_name}" "${db_user}" "${db_pass}" "${db_host}:${db_port}" "${wp_version}"
