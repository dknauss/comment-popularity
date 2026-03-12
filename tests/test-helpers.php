<?php namespace CommentPopularity;

/**
 * Template helper regression tests.
 */
class Test_HMN_CP_Helpers extends \WP_UnitTestCase {

	/**
	 * @var HMN_Comment_Popularity
	 */
	protected $plugin;

	/**
	 * @var int
	 */
	protected $comment_author_id;

	/**
	 * @var int
	 */
	protected $post_id;

	/**
	 * @var int
	 */
	protected $comment_id;

	public function setUp(): void {
		parent::setUp();

		$this->plugin = HMN_Comment_Popularity::get_instance();
		$visitor_prop = new \ReflectionProperty( $this->plugin, 'visitor' );
		$visitor_prop->setValue( $this->plugin, null );

		$this->comment_author_id = $this->factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'helper_comment_author',
				'email'      => 'helper-comment-author@example.com',
			)
		);

		$author_role = get_role( 'author' );
		if ( $author_role && ! $author_role->has_cap( 'vote_on_comments' ) ) {
			$author_role->add_cap( 'vote_on_comments' );
		}

		$this->post_id    = $this->factory->post->create();
		$this->comment_id = $this->factory->comment->create(
			array(
				'comment_post_ID'      => $this->post_id,
				'user_id'              => $this->comment_author_id,
				'comment_author_email' => 'helper-comment-author@example.com',
			)
		);

		$GLOBALS['post'] = get_post( $this->post_id );
	}

	public function tearDown(): void {
		delete_user_option( $this->comment_author_id, 'hmn_user_karma' );

		unset( $GLOBALS['comment'] );
		unset( $GLOBALS['post'] );

		wp_delete_comment( $this->comment_id, true );
		wp_delete_post( $this->post_id, true );
		wp_delete_user( $this->comment_author_id );

		parent::tearDown();
	}

	public function test_the_comment_author_karma_outputs_registered_author_karma() {
		update_user_option( $this->comment_author_id, 'hmn_user_karma', 9 );
		$GLOBALS['comment'] = get_comment( $this->comment_id );

		ob_start();
		\hmn_cp_the_comment_author_karma();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'User Karma: 9', $output );
	}

	public function test_the_comment_author_karma_returns_empty_for_non_registered_comment_author() {
		$guest_comment_id   = $this->factory->comment->create(
			array(
				'comment_post_ID'      => $this->post_id,
				'user_id'              => 0,
				'comment_author_email' => 'guest-author@example.com',
			)
		);
		$GLOBALS['comment'] = get_comment( $guest_comment_id );

		ob_start();
		\hmn_cp_the_comment_author_karma();
		$output = ob_get_clean();

		$this->assertSame( '', trim( $output ) );

		wp_delete_comment( $guest_comment_id, true );
	}

	public function test_the_sorted_comments_renders_output_with_default_arguments() {
		$GLOBALS['post'] = get_post( $this->post_id );

		ob_start();
		\hmn_cp_the_sorted_comments(
			array(
				'number' => 1,
			)
		);
		$output = ob_get_clean();

		$this->assertNotSame( '', trim( $output ) );
	}
}
