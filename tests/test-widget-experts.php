<?php namespace CommentPopularity;

if ( ! class_exists( 'CommentPopularity\HMN_CP_Widget_Experts_Test_Double', false ) ) {
	/**
	 * Test double to expose protected widget internals for regression tests.
	 */
	class HMN_CP_Widget_Experts_Test_Double extends HMN_CP_Widget_Experts {
		/**
		 * @return mixed
		 */
		public function get_experts_for_test() {
			return $this->get_experts();
		}
	}
}

/**
 * Experts widget regression tests.
 */
class Test_HMN_CP_Widget_Experts extends \WP_UnitTestCase {

	/**
	 * @var HMN_CP_Widget_Experts_Test_Double
	 */
	protected $widget;

	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'CommentPopularity\HMN_CP_Widget_Experts', false ) ) {
			require_once dirname( __DIR__ ) . '/inc/widgets/experts/class-widget-experts.php';
		}

		$this->widget = new HMN_CP_Widget_Experts_Test_Double();
	}

	public function test_get_experts_returns_empty_array_when_no_experts_match_query() {
		$force_empty_result = static function ( \WP_User_Query $query ) {
			$query->set( 'include', array( 0 ) );
		};

		add_action( 'pre_get_users', $force_empty_result );

		$experts = $this->widget->get_experts_for_test();

		remove_action( 'pre_get_users', $force_empty_result );

		$this->assertIsArray( $experts );
		$this->assertSame( array(), $experts );
	}

	public function test_get_gravatar_url_uses_https_scheme() {
		$email = 'Example.Email+test@example.com';
		$hash  = md5( strtolower( trim( $email ) ) );

		$this->assertSame(
			'https://gravatar.com/avatar/' . $hash,
			$this->widget->get_gravatar_url( $email )
		);
	}
}
