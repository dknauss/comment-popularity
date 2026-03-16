#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_LOCAL="${ROOT_DIR}/bin/wp-local-single-site.sh"
COMMENT_ID="${LOCAL_RESET_COMMENT_ID:-1}"
USER_IDS="${LOCAL_RESET_USER_IDS:-1,6}"

if [[ ! -x "$WP_LOCAL" ]]; then
	echo "local-reset-vote-state: missing executable ${WP_LOCAL}" >&2
	exit 1
fi

tmp_eval="$(mktemp)"
cleanup() {
	rm -f "$tmp_eval"
}
trap cleanup EXIT

cat >"$tmp_eval" <<'PHP'
<?php

$comment_id = getenv( 'LOCAL_RESET_COMMENT_ID' );
$user_ids   = getenv( 'LOCAL_RESET_USER_IDS' );

$comment_id = is_string( $comment_id ) ? (int) $comment_id : 0;
$user_ids   = is_string( $user_ids ) ? $user_ids : '';

if ( 0 >= $comment_id || ! get_comment( $comment_id ) ) {
	fwrite( STDERR, "Reset failure: valid LOCAL_RESET_COMMENT_ID is required.\n" );
	exit( 1 );
}

$parsed_user_ids = array_filter(
	array_map(
		'absint',
		array_map( 'trim', explode( ',', $user_ids ) )
	)
);

wp_update_comment(
	array(
		'comment_ID'    => $comment_id,
		'comment_karma' => 0,
	)
);

update_comment_meta( $comment_id, '_hmn_cp_upvotes', 0 );
update_comment_meta( $comment_id, '_hmn_cp_downvotes', 0 );
update_comment_meta( $comment_id, '_hmn_cp_total_votes', 0 );
update_comment_meta( $comment_id, '_hmn_cp_wilson_lb', 0 );
delete_option( 'hmn_cp_guests_logged_votes' );

foreach ( $parsed_user_ids as $user_id ) {
	if ( get_user_by( 'id', $user_id ) ) {
		delete_user_option( $user_id, 'hmn_comments_voted_on' );
	}
}

fwrite( STDOUT, 'Reset comment ' . $comment_id . " vote state.\n" );
fwrite( STDOUT, 'Cleared member vote logs for user IDs: ' . ( '' !== $user_ids ? $user_ids : '(none)' ) . ".\n" );
PHP

echo "[local-reset] Resetting vote state for comment ${COMMENT_ID}"
LOCAL_RESET_COMMENT_ID="$COMMENT_ID" LOCAL_RESET_USER_IDS="$USER_IDS" "$WP_LOCAL" eval-file "$tmp_eval"
