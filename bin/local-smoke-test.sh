#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_LOCAL="${ROOT_DIR}/bin/wp-local-single-site.sh"
WP_URL="${WP_URL:-https://single-site-local.local}"

if [[ ! -x "$WP_LOCAL" ]]; then
	echo "local-smoke-test: missing executable ${WP_LOCAL}" >&2
	exit 1
fi

echo "[smoke] Checking local site availability: ${WP_URL}"
curl -k -fsS "${WP_URL}/wp-json/" >/dev/null

echo "[smoke] Checking plugin activation/version"
plugin_line="$("$WP_LOCAL" plugin list --fields=name,status,version --format=csv | rg '^comment-popularity,')"

if [[ -z "$plugin_line" ]]; then
	echo "local-smoke-test: comment-popularity is not installed in the local site." >&2
	exit 1
fi

echo "[smoke] ${plugin_line}"

tmp_eval="$(mktemp)"
cleanup() {
	rm -f "$tmp_eval"
}
trap cleanup EXIT

cat >"$tmp_eval" <<'PHP'
<?php

use CommentPopularity\HMN_Comment_Popularity;
use CommentPopularity\HMN_CP_Visitor_Member;

$admin_id = 1;

if ( ! get_user_by( 'id', $admin_id ) ) {
	fwrite( STDERR, "Smoke failure: user ID 1 is required in local site.\n" );
	exit( 1 );
}

$role = get_role( 'administrator' );
if ( $role && ! $role->has_cap( 'vote_on_comments' ) ) {
	$role->add_cap( 'vote_on_comments' );
}

wp_set_current_user( $admin_id );

$plugin = HMN_Comment_Popularity::get_instance();
$plugin->set_visitor( new HMN_CP_Visitor_Member( $admin_id ) );

$post_id = wp_insert_post(
	array(
		'post_title'  => 'Comment Popularity Smoke Test',
		'post_status' => 'draft',
		'post_type'   => 'post',
	)
);

if ( is_wp_error( $post_id ) || ! $post_id ) {
	fwrite( STDERR, "Smoke failure: could not create test post.\n" );
	exit( 1 );
}

$comment_id = wp_insert_comment(
	array(
		'comment_post_ID'      => $post_id,
		'comment_author'       => 'Smoke Tester',
		'comment_author_email' => 'smoke@example.com',
		'comment_content'      => 'Smoke test comment',
		'user_id'              => 0,
		'comment_approved'     => 1,
	)
);

if ( ! $comment_id ) {
	wp_delete_post( $post_id, true );
	fwrite( STDERR, "Smoke failure: could not create test comment.\n" );
	exit( 1 );
}

$upvote = $plugin->comment_vote( $admin_id, $comment_id, 'upvote' );
if ( ! empty( $upvote['error_code'] ) ) {
	wp_delete_comment( $comment_id, true );
	wp_delete_post( $post_id, true );
	fwrite( STDERR, "Smoke failure: upvote failed (" . $upvote['error_code'] . ").\n" );
	exit( 1 );
}

$undo = $plugin->comment_vote( $admin_id, $comment_id, 'undo' );
if ( ! empty( $undo['error_code'] ) ) {
	wp_delete_comment( $comment_id, true );
	wp_delete_post( $post_id, true );
	fwrite( STDERR, "Smoke failure: undo failed (" . $undo['error_code'] . ").\n" );
	exit( 1 );
}

$weight = (int) $plugin->get_comment_weight( $comment_id );
if ( 0 !== $weight ) {
	wp_delete_comment( $comment_id, true );
	wp_delete_post( $post_id, true );
	fwrite( STDERR, "Smoke failure: expected weight 0 after undo.\n" );
	exit( 1 );
}

wp_delete_comment( $comment_id, true );
wp_delete_post( $post_id, true );

fwrite( STDOUT, "Smoke vote flow passed.\n" );
PHP

echo "[smoke] Running vote/undo flow via WP-CLI"
"$WP_LOCAL" eval-file "$tmp_eval"

echo "[smoke] Local smoke test passed."
