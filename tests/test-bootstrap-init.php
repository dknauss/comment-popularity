<?php namespace CommentPopularity;

/**
 * Bootstrap init regression tests.
 */
class Test_HMN_CP_Bootstrap_Init extends \WP_UnitTestCase {

	/**
	 * @var HMN_Comment_Popularity
	 */
	protected $plugin;

	/**
	 * @var string|null
	 */
	protected $original_remote_addr;

	public function setUp(): void {
		parent::setUp();

		$this->plugin = HMN_Comment_Popularity::get_instance();
		$this->clear_visitor();

		$this->original_remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : null;

		remove_all_filters( 'hmn_cp_allow_guest_voting' );
		wp_set_current_user( 0 );
	}

	public function tearDown(): void {
		remove_all_filters( 'hmn_cp_allow_guest_voting' );
		$this->clear_visitor();

		if ( null === $this->original_remote_addr ) {
			unset( $_SERVER['REMOTE_ADDR'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Test fixture teardown.
		} else {
			$_SERVER['REMOTE_ADDR'] = $this->original_remote_addr; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Test fixture teardown.
		}

		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Reset singleton visitor to isolate bootstrap tests.
	 */
	protected function clear_visitor() {
		$visitor_prop = new \ReflectionProperty( $this->plugin, 'visitor' );
		$visitor_prop->setValue( $this->plugin, null );
	}

	public function test_hmn_cp_init_sets_member_visitor_for_logged_in_user() {
		$user_id = $this->factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'bootstrap_member',
				'email'      => 'bootstrap-member@example.com',
			)
		);

		wp_set_current_user( $user_id );

		\hmn_cp_init();

		$this->assertInstanceOf( HMN_CP_Visitor_Member::class, $this->plugin->get_visitor() );
		$this->assertSame( $user_id, (int) $this->plugin->get_visitor()->get_id() );

		wp_delete_user( $user_id );
	}

	public function test_hmn_cp_init_returns_without_visitor_when_guest_voting_disabled() {
		wp_set_current_user( 0 );
		$this->clear_visitor();

		\hmn_cp_init();

		$this->assertNull( $this->plugin->get_visitor() );
	}

	public function test_hmn_cp_init_returns_without_guest_visitor_when_remote_addr_missing() {
		add_filter( 'hmn_cp_allow_guest_voting', '__return_true' );
		unset( $_SERVER['REMOTE_ADDR'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Test fixture setup.
		$this->clear_visitor();

		\hmn_cp_init();

		$this->assertNull( $this->plugin->get_visitor() );
	}

	public function test_hmn_cp_init_does_not_overwrite_existing_visitor_instance() {
		$first_user_id  = $this->factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'existing_visitor_user',
				'email'      => 'existing-visitor@example.com',
			)
		);
		$second_user_id = $this->factory->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'new_login_user',
				'email'      => 'new-login@example.com',
			)
		);

		$this->plugin->set_visitor( new HMN_CP_Visitor_Member( $first_user_id ) );
		wp_set_current_user( $second_user_id );

		\hmn_cp_init();

		$this->assertSame( $first_user_id, (int) $this->plugin->get_visitor()->get_id() );

		wp_delete_user( $first_user_id );
		wp_delete_user( $second_user_id );
	}
}
