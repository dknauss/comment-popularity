<?php

use CommentPopularity\HMN_Comment_Popularity;

/**
 * Uninstall routine regression tests.
 */
class Test_HMN_CP_Uninstall extends WP_UnitTestCase {

	/**
	 * @var int[]
	 */
	protected $user_ids = array();

	/**
	 * @var int[]
	 */
	protected $post_ids = array();

	/**
	 * @var int[]
	 */
	protected $comment_ids = array();

	/**
	 * @var int[]
	 */
	protected $site_ids = array();

	public function tearDown(): void {
		$current_blog_id = get_current_blog_id();

		delete_option( 'comment_popularity_prefs' );
		delete_option( 'hmn_cp_plugin_version' );
		delete_option( 'hmn_cp_guests_logged_votes' );
		if ( is_multisite() && function_exists( 'delete_blog_option' ) ) {
			delete_blog_option( $current_blog_id, 'comment_popularity_prefs' );
			delete_blog_option( $current_blog_id, 'hmn_cp_plugin_version' );
			delete_blog_option( $current_blog_id, 'hmn_cp_guests_logged_votes' );
		}

		foreach ( $this->comment_ids as $comment_id ) {
			wp_delete_comment( $comment_id, true );
		}

		foreach ( $this->post_ids as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		foreach ( $this->user_ids as $user_id ) {
			delete_user_option( $user_id, 'hmn_user_expert_status' );
			delete_user_option( $user_id, 'hmn_user_karma' );
			delete_user_option( $user_id, 'hmn_comments_voted_on' );
			wp_delete_user( $user_id );
		}

		foreach ( $this->site_ids as $site_id ) {
			if ( function_exists( 'delete_blog_option' ) ) {
				delete_blog_option( $site_id, 'comment_popularity_prefs' );
				delete_blog_option( $site_id, 'hmn_cp_plugin_version' );
				delete_blog_option( $site_id, 'hmn_cp_guests_logged_votes' );
			}
			if ( function_exists( 'wp_delete_site' ) ) {
				wp_delete_site( $site_id );
			}
		}

		HMN_Comment_Popularity::deactivate();

		parent::tearDown();
	}

	/**
	 * Execute uninstall script under test.
	 */
	protected function run_uninstall() {
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}

		include dirname( __DIR__ ) . '/uninstall.php';
	}

	public function test_uninstall_deletes_plugin_options_and_user_vote_state() {
		$user_id          = $this->factory->user->create();
		$this->user_ids[] = $user_id;

		update_option( 'comment_popularity_prefs', array( 'ranking_mode' => 'karma' ) );
		update_option( 'hmn_cp_plugin_version', '1.5.1' );
		update_option( 'hmn_cp_guests_logged_votes', array( '127.0.0.1' => array( 1 ) ) );

		update_user_option( $user_id, 'hmn_user_expert_status', true );
		update_user_option( $user_id, 'hmn_user_karma', 12 );
		update_user_option(
			$user_id,
			'hmn_comments_voted_on',
			array(
				'comment_id_42' => array(
					'vote_time'   => 1111111111,
					'last_action' => 'upvote',
				),
			)
		);

		$this->run_uninstall();

		$this->assertFalse( get_option( 'comment_popularity_prefs', false ) );
		$this->assertFalse( get_option( 'hmn_cp_plugin_version', false ) );
		$this->assertFalse( get_option( 'hmn_cp_guests_logged_votes', false ) );

		$this->assertFalse( get_user_option( 'hmn_user_expert_status', $user_id ) );
		$this->assertFalse( get_user_option( 'hmn_user_karma', $user_id ) );
		$this->assertFalse( get_user_option( 'hmn_comments_voted_on', $user_id ) );
	}

	public function test_uninstall_resets_comment_karma_and_removes_custom_capabilities() {
		$post_id             = $this->factory->post->create();
		$this->post_ids[]    = $post_id;
		$comment_id          = $this->factory->comment->create( array( 'comment_post_ID' => $post_id ) );
		$this->comment_ids[] = $comment_id;

		wp_update_comment(
			array(
				'comment_ID'    => $comment_id,
				'comment_karma' => 6,
			)
		);

		HMN_Comment_Popularity::set_permissions();

		$this->assertTrue( get_role( 'administrator' )->has_cap( 'manage_user_karma_settings' ) );
		$this->assertTrue( get_role( 'subscriber' )->has_cap( 'vote_on_comments' ) );

		$this->run_uninstall();

		clean_comment_cache( $comment_id );

		$this->assertSame( 0, (int) get_comment( $comment_id )->comment_karma );
		$this->assertFalse( get_role( 'administrator' )->has_cap( 'manage_user_karma_settings' ) );
		$this->assertFalse( get_role( 'subscriber' )->has_cap( 'vote_on_comments' ) );
	}

	public function test_uninstall_deletes_blog_scoped_options_across_multisite_network() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only test.' );
		}

		$current_blog_id  = get_current_blog_id();
		$second_site_id   = self::factory()->blog->create();
		$this->site_ids[] = $second_site_id;

		update_blog_option(
			$current_blog_id,
			'comment_popularity_prefs',
			array( 'ranking_mode' => 'wilson' )
		);
		update_blog_option(
			$current_blog_id,
			'hmn_cp_guests_logged_votes',
			array( '127.0.0.1' => array( 1 ) )
		);
		update_blog_option( $current_blog_id, 'hmn_cp_plugin_version', '1.5.1' );

		update_blog_option(
			$second_site_id,
			'comment_popularity_prefs',
			array( 'ranking_mode' => 'karma' )
		);
		update_blog_option(
			$second_site_id,
			'hmn_cp_guests_logged_votes',
			array( '10.0.0.1' => array( 42 ) )
		);
		update_blog_option( $second_site_id, 'hmn_cp_plugin_version', '1.5.1' );

		$this->run_uninstall();

		$this->assertFalse( get_blog_option( $current_blog_id, 'comment_popularity_prefs', false ) );
		$this->assertFalse( get_blog_option( $current_blog_id, 'hmn_cp_guests_logged_votes', false ) );
		$this->assertFalse( get_blog_option( $current_blog_id, 'hmn_cp_plugin_version', false ) );

		$this->assertFalse( get_blog_option( $second_site_id, 'comment_popularity_prefs', false ) );
		$this->assertFalse( get_blog_option( $second_site_id, 'hmn_cp_guests_logged_votes', false ) );
		$this->assertFalse( get_blog_option( $second_site_id, 'hmn_cp_plugin_version', false ) );
	}
}
