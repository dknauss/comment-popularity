<?php namespace CommentPopularity;

/**
 * Callback-specific die handler for JSON and nonce assertions.
 *
 * @param mixed $message wp_die() message.
 */
function hmn_cp_test_vote_callback_wp_die_handler( $message ) {
	if ( is_scalar( $message ) || null === $message ) {
		$message = (string) $message;
	} else {
		$message = wp_json_encode( $message );
	}

	throw new \RuntimeException( $message );
}

/**
 * Public AJAX callback contract tests.
 */
class Test_HMN_CP_Comment_Vote_Callback extends \WP_UnitTestCase {

	/**
	 * @var HMN_Comment_Popularity
	 */
	protected $plugin;

	/**
	 * @var int
	 */
	protected $voter_id;

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

	/**
	 * @var array
	 */
	protected $original_post = array();

	/**
	 * @var array
	 */
	protected $original_request = array();

	public function setUp(): void {
		parent::setUp();

		$this->plugin = HMN_Comment_Popularity::get_instance();
		$this->clear_visitor();

		$this->original_post    = $_POST;
		$this->original_request = $_REQUEST;

		$this->voter_id = $this->factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'callback_voter',
				'user_email' => 'callback-voter@example.com',
			)
		);

		$this->comment_author_id = $this->factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'callback_comment_author',
				'user_email' => 'callback-comment-author@example.com',
			)
		);

		$role = get_role( 'author' );
		if ( $role && ! $role->has_cap( 'vote_on_comments' ) ) {
			$role->add_cap( 'vote_on_comments' );
		}

		wp_set_current_user( $this->voter_id );
		$this->plugin->set_visitor( new HMN_CP_Visitor_Member( $this->voter_id ) );

		$this->post_id    = $this->factory->post->create();
		$this->comment_id = $this->factory->comment->create(
			array(
				'comment_post_ID'      => $this->post_id,
				'user_id'              => $this->comment_author_id,
				'comment_author_email' => 'callback-comment-author@example.com',
			)
		);

		remove_all_filters( 'hmn_cp_allow_guest_voting' );
	}

	public function tearDown(): void {
		remove_all_filters( 'hmn_cp_allow_guest_voting' );

		delete_user_option( $this->voter_id, 'hmn_comments_voted_on' );
		delete_user_option( $this->comment_author_id, 'hmn_user_karma' );

		wp_delete_comment( $this->comment_id, true );
		wp_delete_post( $this->post_id, true );
		wp_delete_user( $this->voter_id );
		wp_delete_user( $this->comment_author_id );

		$_POST    = $this->original_post;
		$_REQUEST = $this->original_request;

		$this->clear_visitor();
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Reset singleton visitor to isolate callback tests.
	 */
	protected function clear_visitor() {
		$visitor_prop = new \ReflectionProperty( $this->plugin, 'visitor' );
		$visitor_prop->setValue( $this->plugin, null );
	}

	/**
	 * Resolve custom wp_die handler callback.
	 *
	 * @return string
	 */
	public function filter_wp_die_handler() {
		return __NAMESPACE__ . '\\hmn_cp_test_vote_callback_wp_die_handler';
	}

	/**
	 * Execute callback with custom input and capture JSON/die output.
	 *
	 * @param array $post_data Callback request payload.
	 *
	 * @return array{died:bool,die_message:string,output:string}
	 */
	protected function run_callback( array $post_data ) {
		$_POST    = $post_data;
		$_REQUEST = $post_data;

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_handler', array( $this, 'filter_wp_die_handler' ) );
		add_filter( 'wp_die_ajax_handler', array( $this, 'filter_wp_die_handler' ) );

		$die_message = '';
		$died        = false;

		ob_start();
		try {
			$this->plugin->comment_vote_callback();
		} catch ( \RuntimeException $exception ) {
			$died        = true;
			$die_message = $exception->getMessage();
		}
		$output = ob_get_clean();

		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_handler', array( $this, 'filter_wp_die_handler' ) );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'filter_wp_die_handler' ) );

		return array(
			'died'        => $died,
			'die_message' => $die_message,
			'output'      => $output,
		);
	}

	/**
	 * Build callback payload with default valid values.
	 *
	 * @param array $overrides Request overrides.
	 *
	 * @return array
	 */
	protected function build_payload( array $overrides = array() ) {
		$payload = array(
			'hmn_vote_nonce' => wp_create_nonce( 'hmn_vote_submit' ),
			'comment_id'     => $this->comment_id,
			'vote'           => 'upvote',
		);

		return wp_parse_args( $overrides, $payload );
	}

	/**
	 * Decode callback JSON response.
	 *
	 * @param string $output Raw callback output.
	 *
	 * @return array
	 */
	protected function decode_json_response( $output ) {
		$response = json_decode( $output, true );
		$this->assertIsArray( $response );

		return $response;
	}

	public function test_callback_rejects_invalid_nonce() {
		$result = $this->run_callback(
			array(
				'comment_id' => $this->comment_id,
				'vote'       => 'upvote',
			)
		);

		$this->assertTrue( $result['died'] );
		$this->assertSame( '-1', $result['die_message'] );
	}

	public function test_callback_rejects_invalid_comment_id() {
		$result = $this->run_callback(
			$this->build_payload(
				array(
					'comment_id' => 0,
				)
			)
		);

		$this->assertTrue( $result['died'] );

		$response = $this->decode_json_response( $result['output'] );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'invalid_comment_id', $response['data']['error_code'] );
	}

	public function test_callback_rejects_invalid_vote_action() {
		$result = $this->run_callback(
			$this->build_payload(
				array(
					'vote' => 'invalid-vote',
				)
			)
		);

		$this->assertTrue( $result['died'] );

		$response = $this->decode_json_response( $result['output'] );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'invalid_action', $response['data']['error_code'] );
	}

	public function test_callback_returns_invalid_visitor_when_visitor_missing() {
		$this->clear_visitor();

		$result = $this->run_callback( $this->build_payload() );

		$this->assertTrue( $result['died'] );

		$response = $this->decode_json_response( $result['output'] );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'invalid_visitor', $response['data']['error_code'] );
	}

	public function test_callback_returns_invalid_visitor_when_guest_voting_disabled() {
		wp_set_current_user( 0 );
		$this->clear_visitor();

		add_filter( 'hmn_cp_allow_guest_voting', '__return_false' );
		\hmn_cp_init();
		$this->assertNull( $this->plugin->get_visitor() );

		$result = $this->run_callback(
			array(
				'hmn_vote_nonce' => wp_create_nonce( 'hmn_vote_submit' ),
				'comment_id'     => $this->comment_id,
				'vote'           => 'upvote',
			)
		);

		$this->assertTrue( $result['died'] );

		$response = $this->decode_json_response( $result['output'] );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'invalid_visitor', $response['data']['error_code'] );
	}

	public function test_callback_returns_success_for_upvote_downvote_and_undo_sequence() {
		$upvote_result = $this->run_callback(
			$this->build_payload(
				array(
					'vote' => 'upvote',
				)
			)
		);
		$downvote_result = $this->run_callback(
			$this->build_payload(
				array(
					'vote' => 'downvote',
				)
			)
		);
		$undo_result = $this->run_callback(
			$this->build_payload(
				array(
					'vote' => 'undo',
				)
			)
		);

		$upvote_response   = $this->decode_json_response( $upvote_result['output'] );
		$downvote_response = $this->decode_json_response( $downvote_result['output'] );
		$undo_response     = $this->decode_json_response( $undo_result['output'] );

		$this->assertTrue( $upvote_response['success'] );
		$this->assertSame( 'upvote', $upvote_response['data']['vote_type'] );
		$this->assertTrue( $downvote_response['success'] );
		$this->assertSame( 'downvote', $downvote_response['data']['vote_type'] );
		$this->assertTrue( $undo_response['success'] );
		$this->assertSame( 'undo', $undo_response['data']['vote_type'] );
	}
}
