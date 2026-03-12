<?php namespace CommentPopularity;

/**
 * Admin settings and profile UI regression tests.
 */
class Test_HMN_Comment_Popularity_Admin extends \WP_UnitTestCase {

	/**
	 * @var HMN_Comment_Popularity_Admin
	 */
	protected $admin;

	/**
	 * @var int
	 */
	protected $admin_user_id;

	/**
	 * @var int
	 */
	protected $author_user_id;

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

		if ( ! class_exists( 'CommentPopularity\HMN_Comment_Popularity_Admin', false ) ) {
			require_once dirname( __DIR__ ) . '/admin/class-comment-popularity-admin.php';
		}

		$this->admin = HMN_Comment_Popularity_Admin::get_instance();

		$admin_role = get_role( 'administrator' );
		if ( $admin_role && ! $admin_role->has_cap( 'manage_user_karma_settings' ) ) {
			$admin_role->add_cap( 'manage_user_karma_settings' );
		}

		$this->admin_user_id = $this->factory->user->create(
			array(
				'role'       => 'administrator',
				'user_login' => 'admin_ui_tester',
				'email'      => 'admin-ui@example.com',
			)
		);

		if ( is_multisite() ) {
			grant_super_admin( $this->admin_user_id );
		}

		$this->author_user_id = $this->factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'profile_target',
				'email'      => 'profile-target@example.com',
			)
		);

		wp_set_current_user( $this->admin_user_id );

		$this->post_id    = $this->factory->post->create();
		$this->comment_id = $this->factory->comment->create(
			array(
				'comment_post_ID' => $this->post_id,
				'user_id'         => $this->author_user_id,
			)
		);

		$comment_arr                  = get_comment( $this->comment_id, ARRAY_A );
		$comment_arr['comment_karma'] = 4;
		wp_update_comment( $comment_arr );

		update_option(
			'comment_popularity_prefs',
			array(
				'default_expert_karma' => 7,
				'ranking_mode'         => 'karma',
			)
		);
	}

	public function tearDown(): void {
		$_POST = array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test fixture cleanup.

		delete_option( 'comment_popularity_prefs' );
		delete_user_option( $this->author_user_id, 'hmn_user_karma' );
		delete_user_option( $this->author_user_id, 'hmn_user_expert_status' );

		wp_delete_comment( $this->comment_id, true );
		wp_delete_post( $this->post_id, true );
		wp_delete_user( $this->author_user_id );

		if ( is_multisite() ) {
			revoke_super_admin( $this->admin_user_id );
		}

		wp_delete_user( $this->admin_user_id );

		parent::tearDown();
	}

	public function test_validate_settings_sanitizes_values() {
		$validated = $this->admin->validate_settings(
			array(
				'default_expert_karma' => '-12',
				'ranking_mode'         => 'wilson',
			)
		);

		$this->assertSame( 12, $validated['default_expert_karma'] );
		$this->assertSame( 'wilson', $validated['ranking_mode'] );
	}

	public function test_validate_settings_applies_defaults_for_invalid_values() {
		$validated = $this->admin->validate_settings(
			array(
				'default_expert_karma' => 'not-a-number',
				'ranking_mode'         => 'invalid-mode',
			)
		);

		$this->assertSame( 0, $validated['default_expert_karma'] );
		$this->assertSame( 'karma', $validated['ranking_mode'] );
	}

	public function test_add_comment_columns_registers_comment_karma_column() {
		$columns = $this->admin->add_comment_columns( array( 'author' => 'Author' ) );

		$this->assertArrayHasKey( 'comment_karma', $columns );
	}

	public function test_add_users_columns_registers_user_karma_column() {
		$columns = $this->admin->add_users_columns( array( 'username' => 'Username' ) );

		$this->assertArrayHasKey( 'user_karma', $columns );
	}

	public function test_make_columns_sortable_sets_karma_and_weight_keys() {
		$this->assertSame(
			'user_karma',
			$this->admin->make_karma_column_sortable( array() )['user_karma']
		);
		$this->assertSame(
			'comment_karma',
			$this->admin->make_weight_column_sortable( array() )['comment_karma']
		);
	}

	public function test_populate_users_columns_returns_karma_value_for_user_karma_column() {
		update_user_option( $this->author_user_id, 'hmn_user_karma', 11 );

		$value = $this->admin->populate_users_columns( 'default', 'user_karma', $this->author_user_id );

		$this->assertSame( 11, (int) $value );
	}

	public function test_populate_comment_column_outputs_stored_comment_karma() {
		ob_start();
		$this->admin->populate_comment_column( 'comment_karma', $this->comment_id );
		$output = ob_get_clean();

		$this->assertSame( '4', trim( $output ) );
	}

	public function test_render_expert_karma_input_outputs_saved_default_value() {
		ob_start();
		$this->admin->render_expert_karma_input();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'name="comment_popularity_prefs[default_expert_karma]"', $output );
		$this->assertStringContainsString( 'value="7"', $output );
	}

	public function test_render_ranking_mode_input_marks_saved_mode_selected() {
		update_option(
			'comment_popularity_prefs',
			array(
				'default_expert_karma' => 7,
				'ranking_mode'         => 'wilson',
			)
		);

		ob_start();
		$this->admin->render_ranking_mode_input();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'value="wilson"', $output );
		$this->assertMatchesRegularExpression( '/value="wilson".*selected/s', $output );
	}

	public function test_render_user_karma_field_returns_empty_output_without_management_capability() {
		$subscriber_id = $this->factory->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'no-karma-cap',
				'email'      => 'no-karma-cap@example.com',
			)
		);

		wp_set_current_user( $subscriber_id );

		ob_start();
		$this->admin->render_user_karma_field( get_userdata( $this->author_user_id ) );
		$output = ob_get_clean();

		$this->assertSame( '', trim( $output ) );

		wp_delete_user( $subscriber_id );
	}

	public function test_save_user_meta_requires_nonce_and_updates_when_valid() {
		update_user_option( $this->author_user_id, 'hmn_user_karma', 2 );
		update_user_option( $this->author_user_id, 'hmn_user_expert_status', false );

		$result_without_nonce = $this->admin->save_user_meta( $this->author_user_id );
		$this->assertFalse( $result_without_nonce );
		$this->assertSame( 2, (int) get_user_option( 'hmn_user_karma', $this->author_user_id ) );

		$_POST = array( // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test fixture setup.
			'hmn_cp_user_meta_nonce' => wp_create_nonce( 'hmn_cp_user_meta' ),
			'hmn_user_karma'         => '9',
			'hmn_user_expert_status' => '1',
		);

		$this->admin->save_user_meta( $this->author_user_id );

		$this->assertSame( 9, (int) get_user_option( 'hmn_user_karma', $this->author_user_id ) );
		$this->assertTrue( (bool) get_user_option( 'hmn_user_expert_status', $this->author_user_id ) );
	}
}
