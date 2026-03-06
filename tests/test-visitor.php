<?php namespace CommentPopularity;

/**
 * Visitor persistence regression tests.
 */
class Test_HMN_CP_Visitor extends \WP_UnitTestCase {

	protected $user_id;

	protected $comment_id;

	protected $post_id;

	protected $visitor;

	protected $comment_author_id;

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
		delete_user_option( $this->user_id, 'hmn_comments_voted_on' );
		wp_delete_comment( $this->comment_id, true );
		wp_delete_post( $this->post_id, true );
		wp_delete_user( $this->user_id );
		wp_delete_user( $this->comment_author_id );

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
}
