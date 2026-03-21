<?php namespace CommentPopularity;

/**
 * Callback-specific die handler for bootstrap tests.
 *
 * @param mixed $message wp_die() message.
 */
function hmn_cp_test_bootstrap_wp_die_handler( $message ) {
	if ( is_scalar( $message ) || null === $message ) {
		$message = (string) $message;
	} else {
		$message = wp_json_encode( $message );
	}

	throw new \RuntimeException( $message );
}

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

	/**
	 * Resolve custom wp_die handler callback.
	 *
	 * @return string
	 */
	public function filter_wp_die_handler() {
		return __NAMESPACE__ . '\\hmn_cp_test_bootstrap_wp_die_handler';
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

	public function test_admin_class_is_available_via_composer_autoload() {
		$this->assertTrue( class_exists( HMN_Comment_Popularity_Admin::class ) );
	}

	public function test_activate_deactivates_main_plugin_when_wordpress_version_is_too_low() {
		$admin_user_id = $this->factory->user->create(
			array(
				'role'       => 'administrator',
				'user_login' => 'bootstrap_activation_admin',
				'email'      => 'bootstrap-activation-admin@example.com',
			)
		);

		if ( is_multisite() ) {
			grant_super_admin( $admin_user_id );
		}

		wp_set_current_user( $admin_user_id );

		$plugin_basename         = plugin_basename( dirname( __DIR__ ) . '/comment-popularity.php' );
		$original_active_plugins = get_option( 'active_plugins', array() );
		$original_wp_version     = $GLOBALS['wp_version'];
		$GLOBALS['wp_version']   = '6.3';

		update_option( 'active_plugins', array( $plugin_basename ) );
		add_filter( 'wp_die_handler', array( $this, 'filter_wp_die_handler' ) );

		try {
			HMN_Comment_Popularity::activate();
			$this->fail( 'Expected activation to abort on unsupported WordPress.' );
		} catch ( \RuntimeException $exception ) {
			$this->assertStringContainsString( 'requires WordPress version 6.4', $exception->getMessage() );
		}

		$this->assertNotContains( $plugin_basename, get_option( 'active_plugins', array() ) );

		remove_filter( 'wp_die_handler', array( $this, 'filter_wp_die_handler' ) );
		update_option( 'active_plugins', $original_active_plugins );
		$GLOBALS['wp_version'] = $original_wp_version;

		if ( is_multisite() ) {
			revoke_super_admin( $admin_user_id );
		}

		wp_delete_user( $admin_user_id );
	}
}
