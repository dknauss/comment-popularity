<?php namespace CommentPopularity;

/**
 * Wilson ranking and vote transition regression tests.
 */
class Test_HMN_CP_Wilson_Ranking extends \WP_UnitTestCase {

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
	protected $commenter_id;

	/**
	 * @var int
	 */
	protected $post_id;

	/**
	 * @var int
	 */
	protected $comment_id;

	/**
	 * @var int
	 */
	protected $comment_id_b;

	public function setUp(): void {
		parent::setUp();

		$this->plugin = HMN_Comment_Popularity::get_instance();

		$this->voter_id = $this->factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'wilson_voter',
				'email'      => 'wilson-voter@example.com',
			)
		);

		$this->commenter_id = $this->factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'wilson_commenter',
				'email'      => 'wilson-commenter@example.com',
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
				'comment_author_email' => 'wilson-commenter@example.com',
			)
		);

		$this->comment_id_b = $this->factory->comment->create(
			array(
				'comment_post_ID'      => $this->post_id,
				'comment_author_email' => 'wilson-commenter@example.com',
			)
		);

		update_option(
			'comment_popularity_prefs',
			array(
				'default_expert_karma' => 0,
				'ranking_mode'         => 'wilson',
			)
		);

		add_filter( 'hmn_cp_interval', array( $this, 'disable_vote_interval' ) );
	}

	public function tearDown(): void {
		remove_filter( 'hmn_cp_interval', array( $this, 'disable_vote_interval' ) );

		delete_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_UPVOTES );
		delete_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_DOWNVOTES );
		delete_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_TOTAL_VOTES );
		delete_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_WILSON_LOWER_BOUND );
		delete_comment_meta( $this->comment_id_b, HMN_Comment_Popularity::COMMENT_META_UPVOTES );
		delete_comment_meta( $this->comment_id_b, HMN_Comment_Popularity::COMMENT_META_DOWNVOTES );
		delete_comment_meta( $this->comment_id_b, HMN_Comment_Popularity::COMMENT_META_TOTAL_VOTES );
		delete_comment_meta( $this->comment_id_b, HMN_Comment_Popularity::COMMENT_META_WILSON_LOWER_BOUND );

		delete_user_option( $this->voter_id, 'hmn_comments_voted_on' );
		delete_option( 'comment_popularity_prefs' );

		wp_delete_comment( $this->comment_id, true );
		wp_delete_comment( $this->comment_id_b, true );
		wp_delete_post( $this->post_id, true );
		wp_delete_user( $this->voter_id );
		wp_delete_user( $this->commenter_id );

		parent::tearDown();
	}

	/**
	 * Remove vote throttling for deterministic tests.
	 *
	 * @return int
	 */
	public function disable_vote_interval() {
		return 0;
	}

	public function test_upvote_persists_wilson_vote_counters() {
		$this->plugin->comment_vote( $this->voter_id, $this->comment_id, 'upvote' );

		$this->assertSame( 1, (int) get_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_UPVOTES, true ) );
		$this->assertSame( 0, (int) get_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_DOWNVOTES, true ) );
		$this->assertSame( 1, (int) get_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_TOTAL_VOTES, true ) );
		$this->assertSame( 1, $this->plugin->get_comment_weight( $this->comment_id ) );
		$this->assertGreaterThan( 0.0, (float) get_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_WILSON_LOWER_BOUND, true ) );
	}

	public function test_undo_removes_prior_vote_from_wilson_counts() {
		$this->plugin->comment_vote( $this->voter_id, $this->comment_id, 'upvote' );
		$this->plugin->comment_vote( $this->voter_id, $this->comment_id, 'undo' );

		$this->assertSame( 0, (int) get_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_UPVOTES, true ) );
		$this->assertSame( 0, (int) get_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_DOWNVOTES, true ) );
		$this->assertSame( 0, (int) get_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_TOTAL_VOTES, true ) );
		$this->assertSame( 0.0, (float) get_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_WILSON_LOWER_BOUND, true ) );
	}

	public function test_switch_vote_updates_counter_distribution() {
		$this->plugin->comment_vote( $this->voter_id, $this->comment_id, 'upvote' );
		$this->plugin->comment_vote( $this->voter_id, $this->comment_id, 'downvote' );

		$this->assertSame( 0, (int) get_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_UPVOTES, true ) );
		$this->assertSame( 1, (int) get_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_DOWNVOTES, true ) );
		$this->assertSame( 1, (int) get_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_TOTAL_VOTES, true ) );
		$this->assertSame( 0, $this->plugin->get_comment_weight( $this->comment_id ) );
	}

	public function test_repeat_vote_returns_error_without_changing_legacy_or_wilson_state() {
		$first_vote  = $this->plugin->comment_vote( $this->voter_id, $this->comment_id, 'upvote' );
		$second_vote = $this->plugin->comment_vote( $this->voter_id, $this->comment_id, 'upvote' );

		$this->assertSame( 'voting_flood', $second_vote['error_code'] );
		$this->assertSame( $first_vote['weight'], $this->plugin->get_comment_weight( $this->comment_id ) );
		$this->assertSame( 1, (int) get_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_UPVOTES, true ) );
		$this->assertSame( 1, (int) get_comment_meta( $this->comment_id, HMN_Comment_Popularity::COMMENT_META_TOTAL_VOTES, true ) );
	}

	public function test_invalid_ranking_mode_falls_back_to_karma() {
		update_option(
			'comment_popularity_prefs',
			array(
				'default_expert_karma' => 0,
				'ranking_mode'         => 'nonsense',
			)
		);

		$this->assertSame( 'karma', $this->plugin->get_comment_ranking_mode() );
	}

	public function test_default_ranking_mode_is_karma() {
		delete_option( 'comment_popularity_prefs' );

		$this->assertSame( 'karma', $this->plugin->get_comment_ranking_mode() );
	}

	public function test_wilson_sorting_prefers_higher_wilson_score() {
		$this->plugin->comment_vote( $this->voter_id, $this->comment_id, 'upvote' );

		$second_voter_id = $this->factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'wilson_second_voter',
				'email'      => 'wilson-second-voter@example.com',
			)
		);

		wp_set_current_user( $second_voter_id );
		$this->plugin->set_visitor( new HMN_CP_Visitor_Member( $second_voter_id ) );
		$this->plugin->comment_vote( $second_voter_id, $this->comment_id, 'upvote' );

		wp_set_current_user( $this->voter_id );
		$this->plugin->set_visitor( new HMN_CP_Visitor_Member( $this->voter_id ) );
		$this->plugin->comment_vote( $this->voter_id, $this->comment_id_b, 'downvote' );

		$comments = $this->plugin->get_comments_sorted_by_weight(
			array(
				'post_id' => $this->post_id,
				'number'  => 2,
			),
			false
		);

		$this->assertSame( $this->comment_id, (int) $comments[0]->comment_ID );

		delete_user_option( $second_voter_id, 'hmn_comments_voted_on' );
		wp_delete_user( $second_voter_id );
	}
}
