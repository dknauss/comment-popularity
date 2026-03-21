<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin settings.
$plugin_options = array(
	'comment_popularity_prefs',
	'hmn_cp_plugin_version',
	'hmn_cp_guests_logged_votes',
);

foreach ( $plugin_options as $plugin_option ) {
	delete_option( $plugin_option );
}

if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
		)
	);

	foreach ( $site_ids as $site_id ) {
		foreach ( $plugin_options as $plugin_option ) {
			delete_blog_option( $site_id, $plugin_option );
		}
	}
}

global $wpdb;

if ( ! $wpdb instanceof wpdb ) {
	return;
}

if ( ! function_exists( 'hmn_cp_delete_user_option_records' ) ) {
	/**
	 * Delete site-scoped user option data for an option name.
	 *
	 * @param int    $site_id Site ID.
	 * @param string $option_name User option name.
	 *
	 * @return void
	 */
	function hmn_cp_delete_user_option_records( int $site_id, string $option_name ): void {
		global $wpdb;
		/** @var \wpdb $wpdb */
		$database = $wpdb;

		$restore_blog = false;
		if ( is_multisite() && get_current_blog_id() !== $site_id ) {
			switch_to_blog( $site_id );
			$restore_blog = true;
		}

		$prefixed_option = $database->get_blog_prefix() . $option_name;
		$user_query      = new WP_User_Query(
			array(
				'meta_query' => array(
					array(
						'key'     => $prefixed_option,
						'compare' => 'EXISTS',
					),
				),
				'fields'     => 'all',
			)
		);

		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {
				delete_user_meta( $user->ID, $option_name );
				delete_user_meta( $user->ID, $prefixed_option );
				delete_user_option( $user->ID, $option_name );
			}
		}

		if ( $restore_blog ) {
			restore_current_blog();
		}
	}
}

$site_ids = is_multisite() ? get_sites( array( 'fields' => 'ids' ) ) : array( get_current_blog_id() );

foreach ( $site_ids as $site_id ) {
	hmn_cp_delete_user_option_records( $site_id, 'hmn_user_expert_status' );
	hmn_cp_delete_user_option_records( $site_id, 'hmn_user_karma' );
	hmn_cp_delete_user_option_records( $site_id, 'hmn_comments_voted_on' );
}

// Reset any stored comment karma value.
/** @var \wpdb $wpdb */
$database = $wpdb;

$database->query(
	(string) $database->prepare(
		"UPDATE {$database->comments} SET comment_karma=0 WHERE comment_karma != %d",
		0
	)
);

// Remove custom capabilities
require_once plugin_dir_path( __FILE__ ) . 'inc/class-comment-popularity.php';
CommentPopularity\HMN_Comment_Popularity::deactivate();
