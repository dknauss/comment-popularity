#!/usr/bin/env bash

set -euo pipefail

if [[ $# -lt 1 ]]; then
	echo "Usage: $0 <php|phpdbg> [args...]" >&2
	exit 2
fi

runtime="$1"
shift

if [[ "$runtime" != "php" && "$runtime" != "phpdbg" ]]; then
	echo "Unsupported runtime: $runtime (expected php or phpdbg)" >&2
	exit 2
fi

is_php_compatible() {
	local php_path="$1"
	"${php_path}" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);'
}

find_php_bin() {
	if [[ -n "${CP_PHP_BIN:-}" ]]; then
		if [[ ! -x "${CP_PHP_BIN}" ]]; then
			echo "Configured CP_PHP_BIN is not executable: ${CP_PHP_BIN}" >&2
			exit 2
		fi

		if ! is_php_compatible "${CP_PHP_BIN}"; then
			echo "Configured CP_PHP_BIN must point to PHP 8.2+." >&2
			exit 2
		fi

		echo "${CP_PHP_BIN}"
		return
	fi

	local default_php
	default_php="$(command -v php)"
	local selected_php="${default_php}"

	# Keep the current runtime unless it is below the project floor (PHP 8.2+).
	if ! is_php_compatible "${default_php}"; then
		shopt -s nullglob
		local candidates=(
			"${HOME}/Library/Application Support/Local/lightning-services/php-8.4."*/bin/darwin-arm64/bin/php
			"${HOME}/Library/Application Support/Local/lightning-services/php-8.4."*/bin/darwin/bin/php
			"${HOME}/Library/Application Support/Local/lightning-services/php-8.3."*/bin/darwin-arm64/bin/php
			"${HOME}/Library/Application Support/Local/lightning-services/php-8.3."*/bin/darwin/bin/php
			"${HOME}/Library/Application Support/Local/lightning-services/php-8.2."*/bin/darwin-arm64/bin/php
			"${HOME}/Library/Application Support/Local/lightning-services/php-8.2."*/bin/darwin/bin/php
		)
		shopt -u nullglob

		local candidate
		for candidate in "${candidates[@]}"; do
			if [[ -x "${candidate}" ]] && is_php_compatible "${candidate}"; then
				selected_php="${candidate}"
				break
			fi
		done
	fi

	if ! is_php_compatible "${selected_php}"; then
		echo "Unable to find a PHP 8.2+ binary. Set CP_PHP_BIN to a compatible PHP executable." >&2
		exit 2
	fi

	echo "${selected_php}"
}

resolve_runtime_bin() {
	local php_bin="$1"
	local runtime_name="$2"

	if [[ "${runtime_name}" == "php" ]]; then
		echo "${php_bin}"
		return
	fi

	local php_dir
	php_dir="$(dirname "${php_bin}")"

	if [[ -x "${php_dir}/phpdbg" ]]; then
		echo "${php_dir}/phpdbg"
		return
	fi

	if command -v phpdbg >/dev/null 2>&1; then
		command -v phpdbg
		return
	fi

	echo "Unable to resolve phpdbg binary for runtime wrapper." >&2
	exit 2
}

php_bin="$(find_php_bin)"
runtime_bin="$(resolve_runtime_bin "${php_bin}" "${runtime}")"

exec "${runtime_bin}" "$@"
