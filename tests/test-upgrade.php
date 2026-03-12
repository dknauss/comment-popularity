<?php

use CommentPopularity\HMN_Comment_Popularity;

/**
 * Upgrade routine regression tests.
 */
class Test_HMN_CP_Upgrade extends WP_UnitTestCase {

	/**
	 * @var int[]
	 */
	protected $user_ids = array();

	public function tearDown(): void {
		delete_option( 'hmn_cp_plugin_version' );

		foreach ( $this->user_ids as $user_id ) {
			delete_user_option( $user_id, 'comments_voted_on', true );
			delete_user_option( $user_id, 'hmn_comments_voted_on' );
			wp_delete_user( $user_id );
		}

		parent::tearDown();
	}

	public function test_trigger_upgrades_sets_version_for_new_install() {
		delete_option( 'hmn_cp_plugin_version' );

		hmn_cp_trigger_upgrades();

		$this->assertSame(
			HMN_Comment_Popularity::HMN_CP_PLUGIN_VERSION,
			get_option( 'hmn_cp_plugin_version' )
		);
	}

	public function test_trigger_upgrades_migrates_legacy_comments_voted_option() {
		$user_id           = $this->factory->user->create();
		$this->user_ids[]  = $user_id;
		$legacy_vote_state = array(
			'comment_id_42' => array(
				'vote_time'   => 1111111111,
				'last_action' => 'upvote',
			),
		);

		update_user_option( $user_id, 'comments_voted_on', $legacy_vote_state, true );
		delete_user_option( $user_id, 'hmn_comments_voted_on' );
		delete_option( 'hmn_cp_plugin_version' );

		hmn_cp_trigger_upgrades();

		$this->assertSame(
			$legacy_vote_state,
			get_user_option( 'hmn_comments_voted_on', $user_id )
		);
		$this->assertFalse( get_user_option( 'comments_voted_on', $user_id ) );
		$this->assertSame(
			HMN_Comment_Popularity::HMN_CP_PLUGIN_VERSION,
			get_option( 'hmn_cp_plugin_version' )
		);
	}

	public function test_trigger_upgrades_noops_when_version_is_current() {
		$user_id           = $this->factory->user->create();
		$this->user_ids[]  = $user_id;
		$legacy_vote_state = array(
			'comment_id_99' => array(
				'vote_time'   => 2222222222,
				'last_action' => 'downvote',
			),
		);

		update_option( 'hmn_cp_plugin_version', HMN_Comment_Popularity::HMN_CP_PLUGIN_VERSION );
		update_user_option( $user_id, 'comments_voted_on', $legacy_vote_state, true );
		delete_user_option( $user_id, 'hmn_comments_voted_on' );

		hmn_cp_trigger_upgrades();

		$this->assertSame( $legacy_vote_state, get_user_option( 'comments_voted_on', $user_id ) );
		$this->assertFalse( get_user_option( 'hmn_comments_voted_on', $user_id ) );
	}

	public function test_v121_upgrade_skips_users_without_legacy_option() {
		$user_id          = $this->factory->user->create();
		$this->user_ids[] = $user_id;
		$user             = get_userdata( $user_id );

		hmn_cp_v121_upgrade( array( $user ) );

		$this->assertFalse( get_user_option( 'hmn_comments_voted_on', $user_id ) );
		$this->assertFalse( get_user_option( 'comments_voted_on', $user_id ) );
	}
}
