<?php namespace CommentPopularity;

/**
 * Guest test double that avoids real header writes in CLI tests.
 */
class HMN_CP_Test_Visitor_Guest extends HMN_CP_Visitor_Guest {

	/**
	 * Set cookie state without calling setcookie() in tests.
	 */
	public function set_cookie() {
		$_COOKIE['hmn_cp_visitor'] = (string) $this->visitor_id;
		$this->cookie              = sanitize_text_field( wp_unslash( $_COOKIE['hmn_cp_visitor'] ) );
	}
}

/**
 * Visitor persistence regression tests.
 */
class Test_HMN_CP_Visitor extends \WP_UnitTestCase {

	protected $user_id;

	protected $comment_id;

	protected $post_id;

	protected $visitor;

	protected $comment_author_id;

	/**
	 * @var int[]
	 */
	protected $site_ids = array();

	public function setUp(): void {
		parent::setUp();

		$this->user_id = $this->factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'visitor_tester',
				'email'      => 'visitor@example.com',
			)
		);

		$this->comment_author_id = $this->factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'comment_author',
				'email'      => 'comment-author@example.com',
			)
		);

		$role = get_role( 'author' );
		if ( $role && ! $role->has_cap( 'vote_on_comments' ) ) {
			$role->add_cap( 'vote_on_comments' );
		}

		wp_set_current_user( $this->user_id );
		$this->visitor = new HMN_CP_Visitor_Member( $this->user_id );

		$this->post_id    = $this->factory->post->create();
		$this->comment_id = $this->factory->comment->create(
			array(
				'comment_post_ID' => $this->post_id,
				'user_id'         => $this->comment_author_id,
			)
		);
	}

	public function tearDown(): void {
		$current_blog_id = get_current_blog_id();

		delete_user_option( $this->user_id, 'hmn_comments_voted_on' );
		delete_option( 'hmn_cp_guests_logged_votes' );
		if ( is_multisite() && function_exists( 'delete_blog_option' ) ) {
			delete_blog_option( $current_blog_id, 'hmn_cp_guests_logged_votes' );
		}

		wp_delete_comment( $this->comment_id, true );
		wp_delete_post( $this->post_id, true );
		wp_delete_user( $this->user_id );
		wp_delete_user( $this->comment_author_id );

		foreach ( $this->site_ids as $site_id ) {
			if ( function_exists( 'delete_blog_option' ) ) {
				delete_blog_option( $site_id, 'hmn_cp_guests_logged_votes' );
			}
			if ( function_exists( 'wp_delete_site' ) ) {
				wp_delete_site( $site_id );
			}
		}

		parent::tearDown();
	}

	public function test_member_log_vote_persists_vote_data() {
		$logged_vote  = $this->visitor->log_vote( $this->comment_id, 'upvote' );
		$stored_votes = $this->visitor->retrieve_logged_votes();

		$this->assertSame( 'upvote', $logged_vote['last_action'] );
		$this->assertSame( 'upvote', $stored_votes[ 'comment_id_' . $this->comment_id ]['last_action'] );
	}

	public function test_member_unlog_vote_removes_vote_data() {
		$this->visitor->log_vote( $this->comment_id, 'upvote' );
		$this->visitor->unlog_vote( $this->comment_id );

		$this->assertSame( array(), $this->visitor->retrieve_logged_votes() );
		$this->assertSame( array(), get_user_option( 'hmn_comments_voted_on', $this->user_id ) );
	}

	public function test_member_vote_requires_vote_capability() {
		$role = get_role( 'author' );
		$role->remove_cap( 'vote_on_comments' );
		wp_set_current_user( 0 );
		wp_set_current_user( $this->user_id );

		$result = $this->visitor->is_vote_valid( $this->comment_id, 'upvote' );

		$this->assertWPError( $result );
		$this->assertSame( 'insufficient_permissions', $result->get_error_code() );

		$role->add_cap( 'vote_on_comments' );
	}

	public function test_member_visitor_creation_does_not_consult_vote_interval_filter() {
		$filter_calls = 0;
		$callback     = static function ( $interval ) use ( &$filter_calls ) {
			++$filter_calls;
			return $interval;
		};

		add_filter( 'hmn_cp_interval', $callback );
		new HMN_CP_Visitor_Member( $this->user_id );
		remove_filter( 'hmn_cp_interval', $callback );

		$this->assertSame( 0, $filter_calls );
	}

	public function test_guest_log_vote_persists_vote_data_for_same_guest_identity() {
		$guest_visitor_id = '203.0.113.21';

		$guest_visitor = new HMN_CP_Test_Visitor_Guest( $guest_visitor_id );
		$logged_vote   = $guest_visitor->log_vote( $this->comment_id, 'upvote' );

		$guest_reader = new HMN_CP_Test_Visitor_Guest( $guest_visitor_id );
		$stored_votes = $guest_reader->retrieve_logged_votes();
		$comment_key  = 'comment_id_' . $this->comment_id;

		$this->assertSame( 'upvote', $logged_vote['last_action'] );
		$this->assertArrayHasKey( $comment_key, $stored_votes );
		$this->assertSame( 'upvote', $stored_votes[ $comment_key ]['last_action'] );
	}

	public function test_guest_votes_are_isolated_per_blog_on_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite only test.' );
		}

		$guest_visitor_id = '198.51.100.44';
		$primary_blog_id  = get_current_blog_id();
		$second_site_id   = self::factory()->blog->create();
		$this->site_ids[] = $second_site_id;

		$primary_guest = new HMN_CP_Test_Visitor_Guest( $guest_visitor_id );
		$primary_guest->log_vote( $this->comment_id, 'upvote' );
		$primary_key = 'comment_id_' . $this->comment_id;

		switch_to_blog( $second_site_id );

		$secondary_post_id    = self::factory()->post->create();
		$secondary_comment_id = self::factory()->comment->create(
			array(
				'comment_post_ID' => $secondary_post_id,
			)
		);

		$secondary_guest = new HMN_CP_Test_Visitor_Guest( $guest_visitor_id );
		$secondary_guest->log_vote( $secondary_comment_id, 'downvote' );
		$secondary_votes = $secondary_guest->retrieve_logged_votes();
		$secondary_key   = 'comment_id_' . $secondary_comment_id;

		$this->assertArrayHasKey( $secondary_key, $secondary_votes );
		$this->assertSame( 'downvote', $secondary_votes[ $secondary_key ]['last_action'] );

		wp_delete_comment( $secondary_comment_id, true );
		wp_delete_post( $secondary_post_id, true );
		restore_current_blog();

		$this->assertSame( $primary_blog_id, get_current_blog_id() );

		$primary_reader = new HMN_CP_Test_Visitor_Guest( $guest_visitor_id );
		$primary_votes  = $primary_reader->retrieve_logged_votes();

		$this->assertArrayHasKey( $primary_key, $primary_votes );
		$this->assertArrayNotHasKey( $secondary_key, $primary_votes );
	}
}
