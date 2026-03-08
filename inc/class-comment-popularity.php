<?php namespace CommentPopularity;

/**
 * Class HMN_Comment_Popularity
 *
 * @package CommentPopularity
 */
class HMN_Comment_Popularity {

	/**
	 * Plugin version number.
	 */
	const HMN_CP_PLUGIN_VERSION = '1.5.2-dev';

	/**
	 * The minimum PHP version compatibility.
	 */
	const HMN_CP_REQUIRED_PHP_VERSION = '5.3.2';

	/**
	 *
	 */
	const HMN_CP_REQUIRED_WP_VERSION = '4.9';

	const COMMENT_META_UPVOTES = '_hmn_cp_upvotes';

	const COMMENT_META_DOWNVOTES = '_hmn_cp_downvotes';

	const COMMENT_META_WILSON_LOWER_BOUND = '_hmn_cp_wilson_lb';

	const COMMENT_META_TOTAL_VOTES = '_hmn_cp_total_votes';

	/**
	 * The instance of HMN_Comment_Popularity.
	 *
	 * @var HMN_Comment_Popularity the single class instance.
	 */
	private static $instance;

	/**
	 * The instance of Twig\Environment
	 *
	 * @var \Twig\Environment|null
	 */
	protected $twig;

	/**
	 * @var bool
	 */
	protected $sort_comments_by_weight = true;

	/**
	 * @var bool
	 */
	protected $allow_guest_voting = false;

	/**
	 * @var bool
	 */
	protected $allow_negative_comment_weight = false;

	/**
	 * @var HMN_CP_Visitor
	 */
	protected $visitor;

	/**
	 * Provides access to the class instance
	 *
	 * @return HMN_Comment_Popularity
	 */
	public static function get_instance() {

		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();

		}

		return self::$instance;
	}

	/**
	 * Creates a new HMN_Comment_Popularity object, and registers with WP hooks.
	 */
	private function __construct() {

		$this->includes();

		add_action( 'wp_insert_comment', array( $this, 'insert_comment_callback' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_ajax_comment_vote_callback', array( $this, 'comment_vote_callback' ) );
		add_action( 'wp_ajax_nopriv_comment_vote_callback', array( $this, 'comment_vote_callback' ) );

		add_action( 'init', array( $this, 'load_textdomain' ) );

		add_filter( 'comments_template', array( $this, 'custom_comments_template' ) );

		add_action( 'widgets_init', array( $this, 'register_widgets' ) );

		add_action( 'wp_head', array( $this, 'styles' ) );

		$this->init_twig();
	}

	public function styles() {
		$styles = '<style>.comment-weight-container .upvote a, .comment-weight-container .downvote a, .comment-weight-container span.upvote, .comment-weight-container span.downvote {color:red !important;}</style>';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered CSS output is intentionally emitted as HTML.
		echo apply_filters( 'hmn_cp_inline_styles', $styles );
	}

	/**
	 * Initialize the visitor object.
	 *
	 * @param HMN_CP_Visitor $visitor
	 */
	public function set_visitor( HMN_CP_Visitor $visitor ) {
		$this->visitor = $visitor;
	}

	/**
	 * @return HMN_CP_Visitor
	 */
	public function get_visitor() {
		return $this->visitor;
	}

	/*
	 * Include required files.
	 */
	/**
	 *
	 */
	protected function includes() {

		// Load our dependencies
		require_once plugin_dir_path( __FILE__ ) . 'lib/autoload.php';

		// Widgets
		require_once plugin_dir_path( __FILE__ ) . 'widgets/class-widget-most-voted.php';
		require_once plugin_dir_path( __FILE__ ) . 'widgets/experts/class-widget-experts.php';

		// Visitor
		require_once plugin_dir_path( __FILE__ ) . 'class-visitor.php';
	}

	/**
	 * Register the plugin widgets.
	 */
	public function register_widgets() {

		register_widget( 'CommentPopularity\HMN_CP_Widget_Most_Voted' );
		register_widget( 'CommentPopularity\HMN_CP_Widget_Experts' );
	}

	/**
	 * Returns the value of an upvote or downvote.
	 *
	 * @param $type ( 'upvote' or 'downvote' )
	 *
	 * @return int|mixed|void
	 */
	public function get_vote_value( $type ) {

		switch ( $type ) {

			case 'upvote':
				$value = apply_filters( 'hmn_cp_upvote_value', 1 );
				break;

			case 'downvote':
				$value = apply_filters( 'hmn_cp_downvote_value', - 1 );
				break;

			case 'undo':
				$value = 0;
				break;

			default:
				$value = new \WP_Error( 'invalid_vote_type', __( 'Sorry, invalid vote type', 'comment-popularity' ) );
				break;

		}

		return $value;
	}

	/**
	 * @return array
	 */
	public function get_vote_labels() {
		return array(
			'upvote'   => _x( 'upvote', 'verb', 'comment-popularity' ),
			'downvote' => _x( 'downvote', 'verb', 'comment-popularity' ),
			'undo'     => _x( 'undo', 'verb', 'comment-popularity' ),
		);
	}

	/**
	 * Run checks on plugin activation.
	 */
	public static function activate() {

		global $wp_version;

		if ( ! current_user_can( 'activate_plugins' ) || version_compare( $wp_version, self::HMN_CP_REQUIRED_WP_VERSION, '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			/* translators: the plugin version number */
			wp_die( sprintf( esc_html__( 'This plugin requires WordPress version %s. Sorry about that.', 'comment-popularity' ), esc_html( self::HMN_CP_REQUIRED_WP_VERSION ) ), 'Comment Popularity', array( 'back_link' => true ) );
		}

		self::set_permissions();
	}

	/**
	 * Tasks to perform when plugin is deactivated.
	 */
	public static function deactivate() {

		foreach ( get_editable_roles() as $role ) {

			$role_obj = get_role( strtolower( $role['name'] ) );

			if ( ! empty( $role_obj ) ) {

				if ( $role_obj->has_cap( 'manage_user_karma_settings' ) ) {
					$role_obj->remove_cap( 'manage_user_karma_settings' );
				}

				if ( $role_obj->has_cap( 'vote_on_comments' ) ) {
					$role_obj->remove_cap( 'vote_on_comments' );
				}
			}
		}
	}

	/**
	 * Instantiates the Twig objects.
	 */
	public function init_twig() {

		$template_path = apply_filters( 'hmn_cp_template_path', plugin_dir_path( __FILE__ ) . '/templates' );

		$loader     = new \Twig\Loader\FilesystemLoader( $template_path );
		$this->twig = new \Twig\Environment( $loader );
	}

	/**
	 * Add custom capabilities to allowed roles.
	 */
	public static function set_permissions() {

		$admin_roles = apply_filters( 'hmn_cp_roles', array( 'administrator', 'editor' ) );

		foreach ( $admin_roles as $role ) {

			$role = get_role( $role );

			if ( ! empty( $role ) ) {

				$role->add_cap( 'manage_user_karma_settings' );

			}
		}

		// Allow all user roles to vote.
		global $wp_roles;

		foreach ( $wp_roles->role_objects as $role ) {

			if ( ! empty( $role ) ) {
				$role->add_cap( 'vote_on_comments' );
			}
		}
	}

	/**
	 * Disallow object cloning
	 */
	private function __clone() {
	}

	/**
	 * Load the Javascripts
	 */
	public function enqueue_scripts() {

		wp_enqueue_style( 'growl', plugins_url( '../css/jquery.growl.min.css', __FILE__ ), array(), self::HMN_CP_PLUGIN_VERSION );

		wp_enqueue_script( 'growl', plugins_url( '../js/jquery.growl.min.js', __FILE__ ), array( 'jquery' ), self::HMN_CP_PLUGIN_VERSION, true );

		$js_file = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '../js/voting.js' : '../js/voting.min.js';
		wp_register_script(
			'comment-popularity',
			plugins_url( $js_file, __FILE__ ),
			array(
				'jquery',
				'underscore',
				'growl',
			),
			self::HMN_CP_PLUGIN_VERSION
		);

		$args = array(
			'hmn_vote_nonce' => wp_create_nonce( 'hmn_vote_submit' ),
			'ajaxurl'        => admin_url( 'admin-ajax.php' ),
		);

		wp_localize_script( 'comment-popularity', 'comment_popularity', $args );

		wp_enqueue_script( 'comment-popularity' );
	}

	/**
	 * Override comments template with custom one.
	 *
	 * @return string
	 */
	public function custom_comments_template() {

		global $post;

		if ( ! ( is_singular() && ( have_comments() || 'open' === $post->comment_status ) ) ) {

			return;

		}

		return apply_filters( 'hmn_cp_comments_template_path', plugin_dir_path( __FILE__ ) . 'templates/comments.php' );
	}

	/**
	 * Template for comments and pingbacks.
	 * Used as a callback by wp_list_comments() for displaying the comments.
	 *
	 * @param $comment
	 * @param $args
	 * @param $depth
	 */
	public function comment_callback( $comment, $args, $depth ) {

		include apply_filters( 'hmn_cp_single_comment_template_path', plugin_dir_path( __FILE__ ) . 'templates/comment.php' );
	}

	/**
	 * Renders the HTML for voting on comments
	 *
	 * @param int $comment_id The comment ID.
	 */
	public function render_ui( $comment_id ) {

		$container_classes = array( 'comment-weight-container' );

		if ( ! $this->visitor ) {
			return;
		}
		$votes = $this->visitor->retrieve_logged_votes();
		if ( ! is_array( $votes ) ) {
			$votes = array();
		}

		$comment_ids_voted_on = array();

		foreach ( $votes as $key => $vote ) {
			$comment_ids_voted_on[ str_replace( 'comment_id_', '', $key ) ] = $vote['last_action'];
		}

		$vars = array(
			'container_classes' => $container_classes,
			'comment_id'        => $comment_id,
			'comment_weight'    => $this->get_comment_weight( $comment_id ),
			'comment_wilson'    => $this->get_comment_wilson_score( $comment_id ),
			'enable_voting'     => $this->visitor_can_vote(),
			'ranking_mode'      => $this->get_comment_ranking_mode(),
			'vote_type'         => array_key_exists( $comment_id, $comment_ids_voted_on ) ? $comment_ids_voted_on[ $comment_id ] : '',
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Twig template handles output escaping.
		echo $this->twig->render( 'voting-system.html', $vars );
	}

	/**
	 * @return bool
	 */
	protected function visitor_can_vote() {

		// Visitor can vote if guest voting is enabled, if user is logged in and has correct permission
		return ( null !== $this->visitor ) && ( $this->is_guest_voting_allowed() || ( is_user_logged_in() && current_user_can( 'vote_on_comments' ) ) );
	}

	/**
	 * Retrieves the value for the comment weight data.
	 *
	 * @param $comment_id
	 *
	 * @return int
	 */
	public function get_comment_weight( $comment_id ) {

		$comment = get_comment( $comment_id );

		return (int) $comment->comment_karma;
	}

	/**
	 * Updates the comment weight value in the database.
	 *
	 * @param $vote
	 * @param $comment_id
	 *
	 * @return int
	 */
	public function update_comment_weight( $comment_id, $weight_value ) {

		$comment_arr = get_comment( $comment_id, ARRAY_A );

		$comment_arr['comment_karma'] += $weight_value;

		// Prevent negative weight if not allowed.
		if ( ( ! $this->is_negative_comment_weight_allowed() ) && 0 >= $comment_arr['comment_karma'] ) {
			$comment_arr['comment_karma'] = 0;
		}

		wp_update_comment( $comment_arr );

		$comment_arr = get_comment( $comment_id, ARRAY_A );

		/**
		 * Fires once a comment has been updated.
		 *
		 * @param array $comment_arr The comment data array.
		 */
		do_action( 'hmn_cp_update_comment_weight', $comment_arr );

		return $comment_arr['comment_karma'];
	}

	/**
	 * Sets the initial comment karma.
	 *
	 * @param $comment_id
	 * @param $comment
	 */
	public function insert_comment_callback( $comment_id, $comment ) {

		if ( ! $comment->user_id ) {
			return;
		}

		$user = get_userdata( $comment->user_id );
		if ( ! $user ) {
			return;
		}

		$is_expert = $this->get_comment_author_expert_status( $user->ID );

		$user_karma = $this->get_comment_author_karma( $user->ID );

		if ( $is_expert && ( 0 < $user_karma ) ) {
			$this->update_comment_weight( $comment_id, $user_karma );
		}
	}

	/**
	 * Determine if a comment author has been granted expert status.
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function get_comment_author_expert_status( $user_id ) {

		return (bool) get_user_option( 'hmn_user_expert_status', $user_id );
	}

	/**
	 * Get ranking mode for comment ordering.
	 *
	 * @return string
	 */
	public function get_comment_ranking_mode() {
		$prefs = $this->get_plugin_prefs();
		$mode  = array_key_exists( 'ranking_mode', $prefs ) ? $prefs['ranking_mode'] : 'karma';
		$mode  = apply_filters( 'hmn_cp_comment_ranking_mode', $mode );

		return in_array( $mode, array( 'karma', 'wilson' ), true ) ? $mode : 'karma';
	}

	/**
	 * Read plugin preferences.
	 *
	 * @return array
	 */
	protected function get_plugin_prefs() {
		$defaults = array(
			'default_expert_karma' => 0,
			'ranking_mode'         => 'karma',
		);

		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			return get_blog_option( $blog_id, 'comment_popularity_prefs', $defaults );
		}

		return get_option( 'comment_popularity_prefs', $defaults );
	}

	/**
	 * Get stored Wilson score for a comment.
	 *
	 * @param int $comment_id Comment ID.
	 *
	 * @return float
	 */
	public function get_comment_wilson_score( $comment_id ) {
		return (float) get_comment_meta( $comment_id, self::COMMENT_META_WILSON_LOWER_BOUND, true );
	}

	/**
	 * Read Wilson vote counters for a comment.
	 *
	 * @param int $comment_id Comment ID.
	 *
	 * @return array
	 */
	protected function get_comment_vote_counts( $comment_id ) {

		$upvotes     = (int) get_comment_meta( $comment_id, self::COMMENT_META_UPVOTES, true );
		$downvotes   = (int) get_comment_meta( $comment_id, self::COMMENT_META_DOWNVOTES, true );
		$total_votes = (int) get_comment_meta( $comment_id, self::COMMENT_META_TOTAL_VOTES, true );

		if ( 0 === $total_votes ) {
			$total_votes = $upvotes + $downvotes;
		}

		return array(
			'upvotes'     => max( 0, $upvotes ),
			'downvotes'   => max( 0, $downvotes ),
			'total_votes' => max( 0, $total_votes ),
		);
	}

	/**
	 * Persist Wilson vote counters for a comment.
	 *
	 * @param int   $comment_id Comment ID.
	 * @param array $counts Vote counts.
	 */
	protected function set_comment_vote_counts( $comment_id, array $counts ) {

		update_comment_meta( $comment_id, self::COMMENT_META_UPVOTES, (int) $counts['upvotes'] );
		update_comment_meta( $comment_id, self::COMMENT_META_DOWNVOTES, (int) $counts['downvotes'] );
		update_comment_meta( $comment_id, self::COMMENT_META_TOTAL_VOTES, (int) $counts['total_votes'] );
	}

	/**
	 * Update vote counters from previous/current vote action.
	 *
	 * @param int    $comment_id Comment ID.
	 * @param string $previous_vote Previous vote action.
	 * @param string $vote Current vote action.
	 *
	 * @return array
	 */
	protected function update_comment_vote_counts( $comment_id, $previous_vote, $vote ) {

		$counts = $this->get_comment_vote_counts( $comment_id );

		if ( 'upvote' === $previous_vote ) {
			$counts['upvotes'] = max( 0, $counts['upvotes'] - 1 );
		}

		if ( 'downvote' === $previous_vote ) {
			$counts['downvotes'] = max( 0, $counts['downvotes'] - 1 );
		}

		if ( 'upvote' === $vote ) {
			++$counts['upvotes'];
		}

		if ( 'downvote' === $vote ) {
			++$counts['downvotes'];
		}

		$counts['total_votes'] = $counts['upvotes'] + $counts['downvotes'];

		$this->set_comment_vote_counts( $comment_id, $counts );

		return $counts;
	}

	/**
	 * Calculate Wilson lower bound score.
	 *
	 * @param int   $upvotes Upvotes.
	 * @param int   $downvotes Downvotes.
	 * @param float $z Wilson confidence z-score.
	 *
	 * @return float
	 */
	protected function calculate_wilson_lower_bound( $upvotes, $downvotes, $z ) {

		$total = (int) $upvotes + (int) $downvotes;

		if ( $total <= 0 ) {
			return 0;
		}

		$phat = $upvotes / $total;
		$z2   = $z * $z;

		$numerator   = $phat + $z2 / ( 2 * $total ) - $z * sqrt( ( $phat * ( 1 - $phat ) + $z2 / ( 4 * $total ) ) / $total );
		$denominator = 1 + $z2 / $total;

		return $numerator / $denominator;
	}

	/**
	 * Update Wilson rank metadata for a comment.
	 *
	 * @param int   $comment_id Comment ID.
	 * @param array $counts Vote counts.
	 *
	 * @return float
	 */
	protected function update_comment_wilson_rank( $comment_id, array $counts ) {

		$z     = (float) apply_filters( 'hmn_cp_wilson_z_score', 1.96 );
		$score = $this->calculate_wilson_lower_bound( $counts['upvotes'], $counts['downvotes'], $z );

		update_comment_meta( $comment_id, self::COMMENT_META_WILSON_LOWER_BOUND, $score );

		return $score;
	}

	/**
	 * Get the current visitor vote state for a comment.
	 *
	 * @param int $comment_id Comment ID.
	 *
	 * @return array
	 */
	protected function get_logged_vote_state( $comment_id ) {

		$state = array(
			'last_action' => '',
			'vote_time'   => 0,
		);

		$logged_votes = $this->get_visitor()->retrieve_logged_votes();
		$key          = 'comment_id_' . $comment_id;

		if ( ! is_array( $logged_votes ) || ! isset( $logged_votes[ $key ] ) || ! is_array( $logged_votes[ $key ] ) ) {
			return $state;
		}

		$logged_vote = $logged_votes[ $key ];

		$state['last_action'] = isset( $logged_vote['last_action'] ) ? $logged_vote['last_action'] : '';
		$state['vote_time']   = isset( $logged_vote['vote_time'] ) ? (int) $logged_vote['vote_time'] : 0;

		return $state;
	}

	/**
	 * Calculate the net legacy karma delta for a vote transition.
	 *
	 * @param string $previous_vote Previous visitor vote.
	 * @param string $next_vote Next visitor vote.
	 *
	 * @return int
	 */
	protected function get_vote_delta_from_transition( $previous_vote, $next_vote ) {

		$delta = 0;

		if ( 'upvote' === $previous_vote ) {
			$delta += $this->get_vote_value( 'downvote' );
		} elseif ( 'downvote' === $previous_vote ) {
			$delta += $this->get_vote_value( 'upvote' );
		}

		if ( 'upvote' === $next_vote ) {
			$delta += $this->get_vote_value( 'upvote' );
		} elseif ( 'downvote' === $next_vote ) {
			$delta += $this->get_vote_value( 'downvote' );
		}

		return $delta;
	}

	/**
	 * Resolve the registered user ID for a comment author when possible.
	 *
	 * @param int $comment_id Comment ID.
	 *
	 * @return int
	 */
	protected function get_comment_author_id( $comment_id ) {

		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return 0;
		}

		if ( ! empty( $comment->user_id ) ) {
			return (int) $comment->user_id;
		}

		$email  = get_comment_author_email( $comment_id );
		$author = get_user_by( 'email', $email );

		return ( false !== $author ) ? (int) $author->ID : 0;
	}

	/**
	 * Sorts the comments by weight and returns them.
	 *
	 * @param array|bool $args Query args, or legacy html flag.
	 * @param bool       $html Return html if true.
	 *
	 * @return array|string
	 */
	public function get_comments_sorted_by_weight( $args = array(), $html = false ) {

		// Backward-compatibility: older integrations passed $html first and $args second.
		if ( is_bool( $args ) ) {
			$legacy_html = $args;
			$legacy_args = $html;
			$html        = $legacy_html;
			$args        = is_array( $legacy_args ) ? $legacy_args : array();
		}

		if ( ! is_array( $args ) ) {
			$args = array();
		}

		$ranking_mode = $this->get_comment_ranking_mode();

		// WP_Comment_Query arguments
		$defaults = array(
			'status'  => 'approve',
			'type'    => 'comment',
			'order'   => 'DESC',
			'orderby' => ( 'wilson' === $ranking_mode ) ? 'meta_value_num' : 'comment_karma',
		);

		if ( 'wilson' === $ranking_mode ) {
			$defaults['meta_key'] = self::COMMENT_META_WILSON_LOWER_BOUND;
		}

		$get_comments_args = wp_parse_args( $args, $defaults );

		// The Comment Query
		$comment_query = new \WP_Comment_Query();
		$comments      = $comment_query->query( $get_comments_args );

		if ( $html ) {
			return wp_list_comments( $args, $comments );
		}

		return $comments;
	}

	/**
	 * Ajax handler for the vote action.
	 */
	public function comment_vote_callback() {

		check_ajax_referer( 'hmn_vote_submit', 'hmn_vote_nonce' );

		$comment_id = 0;
		if ( isset( $_POST['comment_id'] ) ) {
			$comment_id = absint( wp_unslash( $_POST['comment_id'] ) );
		}

		$vote = '';
		if ( isset( $_POST['vote'] ) ) {
			$vote = sanitize_key( wp_unslash( $_POST['vote'] ) );
		}

		if ( 0 === $comment_id ) {
			wp_send_json_error( $this->send_error( 'invalid_comment_id', __( 'Invalid comment ID', 'comment-popularity' ), 0 ) );
		}

		if ( ! ( $this->get_visitor() instanceof HMN_CP_Visitor ) ) {
			wp_send_json_error( $this->send_error( 'invalid_visitor', __( 'Voting is unavailable for this user', 'comment-popularity' ), $comment_id ) );
		}

		if ( ! in_array( $vote, array( 'upvote', 'downvote', 'undo' ), true ) ) {

			$return = array(
				'error_code'    => 'invalid_action',
				'error_message' => __( 'Invalid action', 'comment-popularity' ),
				'comment_id'    => $comment_id,
			);

			wp_send_json_error( $return );
		}

		$result = $this->comment_vote( $this->visitor->get_id(), $comment_id, $vote );

		if ( array_key_exists( 'error_message', $result ) ) {

			wp_send_json_error( $result );

		} else {

			wp_send_json_success( $result );

		}
	}

	/**
	 * Fetches the karma for the current user from the database.
	 *
	 * @param $user_id
	 *
	 * @return int
	 */
	public function get_comment_author_karma( $user_id ) {

		// get user meta for karma
		$user_karma = get_user_option( 'hmn_user_karma', $user_id );

		return ( '' !== $user_karma ) ? (int) $user_karma : 0;
	}

	/**
	 * Updates the comment author karma when a comment is voted on.
	 *
	 * @param $commenter_id
	 * @param $value
	 *
	 * @return int|mixed|void
	 */
	public function update_comment_author_karma( $commenter_id, $value ) {

		if ( is_string( $value ) && in_array( $value, array( 'upvote', 'downvote' ), true ) ) {
			$value = $this->get_vote_value( $value );
		}
		$value = (int) $value;

		$user_karma = $this->get_comment_author_karma( $commenter_id );

		// Do not allow negative karma
		if ( 0 === $user_karma && 0 > $value ) {
			return $user_karma;
		}

		$user_karma += $value;

		update_user_option( $commenter_id, 'hmn_user_karma', $user_karma );

		$user_karma = get_user_option( 'hmn_user_karma', $commenter_id );

		/**
		 * Fires once the user meta has been updated for the karma.
		 *
		 * @param int $commenter_id
		 * @param int $user_karma
		 */
		do_action( 'hmn_cp_update_user_karma', $commenter_id, $user_karma );

		return $user_karma;
	}

	/**
	 * Processes the comment vote logic.
	 *
	 * @param $vote
	 * @param $comment_id
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	public function comment_vote( $user_id, $comment_id, $vote ) {

		$labels = $this->get_vote_labels();

		$result = $this->is_vote_valid( $comment_id, $labels, $vote );
		if ( ! empty( $result ) ) {
			return $this->send_error( $result['error_code'], $result['error_msg'], $comment_id );
		}

		$logged_vote   = $this->get_logged_vote_state( $comment_id );
		$previous_vote = $logged_vote['last_action'];
		$next_vote     = ( 'undo' === $vote ) ? '' : $vote;

		if ( '' !== $previous_vote && $previous_vote === $vote ) {
			return $this->send_error( 'voting_flood', __( 'You have already cast this vote.', 'comment-popularity' ), $comment_id );
		}

		if ( 'undo' === $vote && '' === $previous_vote ) {
			return $this->send_error( 'invalid_action', __( 'There is no previous vote to undo.', 'comment-popularity' ), $comment_id );
		}

		$vote_value = $this->get_vote_delta_from_transition( $previous_vote, $next_vote );

		$this->update_comment_weight( $comment_id, $vote_value );

		$author_id = $this->get_comment_author_id( $comment_id );

		// update comment author karma if registered user.
		if ( 0 !== $author_id ) {
			$this->update_comment_author_karma( $author_id, $vote_value );
		}

		$counts       = $this->update_comment_vote_counts( $comment_id, $previous_vote, $next_vote );
		$wilson_score = $this->update_comment_wilson_rank( $comment_id, $counts );

		if ( '' === $next_vote ) {
			$this->get_visitor()->unlog_vote( $comment_id );
		} else {
			$this->get_visitor()->log_vote( $comment_id, $next_vote );
		}

		do_action( 'hmn_cp_comment_vote', $user_id, $comment_id, $labels[ $vote ] );

		$return = array(
			'success_message' => __( 'Thanks for voting!', 'comment-popularity' ),
			'weight'          => $this->get_comment_weight( $comment_id ),
			'comment_id'      => $comment_id,
			'vote_type'       => $labels[ $vote ],
			'ranking_mode'    => $this->get_comment_ranking_mode(),
			'upvotes'         => $counts['upvotes'],
			'downvotes'       => $counts['downvotes'],
			'total_votes'     => $counts['total_votes'],
			'wilson_lb'       => $wilson_score,
		);

		return $return;
	}

	/**
	 * Verify if vote is valid.
	 *
	 * @param int    $comment_id The comment ID.
	 * @param array  $labels The voting labels.
	 * @param string $action What voting action.
	 *
	 * @return array
	 */
	protected function is_vote_valid( $comment_id, $labels, $action ) {
		$user_can_vote = $this->get_visitor()->is_vote_valid( $comment_id, $labels[ $action ] );
		if ( is_wp_error( $user_can_vote ) ) {

			return array(
				'error_code' => $user_can_vote->get_error_code(),
				'error_msg'  => $user_can_vote->get_error_message(),
			);
		}

		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return array(
				'error_code' => 'invalid_comment_id',
				'error_msg'  => __( 'Invalid comment ID', 'comment-popularity' ),
			);
		}

		// Prevent negative weight if not allowed.
		if ( ( 'downvote' === $action && ! $this->is_negative_comment_weight_allowed() ) && 0 >= $comment->comment_karma ) {
			$error_code = 'downvote_zero_karma';
			$error_msg  = __( 'Unable to downvote a comment with no karma', 'comment-popularity' );

			return array(
				'error_code' => $error_code,
				'error_msg'  => $error_msg,
			);
		}

		return array();
	}

	protected function send_error( $error_code, $error_msg, $comment_id ) {
		$return = array(
			'error_code'    => $error_code,
			'error_message' => $error_msg,
			'comment_id'    => $comment_id,
			'vote_type'     => '',
		);

		return $return;
	}

	/**
	 * Loads the plugin language files.
	 *
	 * @return void
	 */
	public function load_textdomain() {

		// Set filter for plugin's languages directory
		$hmn_cp_lang_dir = dirname( plugin_basename( __DIR__ ) ) . '/languages/';
		$hmn_cp_lang_dir = apply_filters( 'hmn_cp_languages_directory', $hmn_cp_lang_dir );

		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), 'comment-popularity' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'comment-popularity', $locale );

		// Setup paths to current locale file
		$mofile_local  = $hmn_cp_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/comment-popularity/' . $mofile;

		if ( file_exists( $mofile_global ) ) {

			// Look in global /wp-content/languages/comment-popularity folder
			load_textdomain( 'comment-popularity', $mofile_global );

		} elseif ( file_exists( $mofile_local ) ) {

			// Look in local /wp-content/plugins/comment-popularity/languages/ folder
			load_textdomain( 'comment-popularity', $mofile_local );

		} else {

			// Load the default language files
			load_plugin_textdomain( 'comment-popularity', false, $hmn_cp_lang_dir );

		}
	}

	/**
	 * Determine if comments are sorted by weight.
	 *
	 * @return mixed|void
	 */
	public function are_comments_sorted_by_weight() {
		return apply_filters( 'hmn_cp_sort_comments_by_weight', $this->sort_comments_by_weight );
	}

	/**
	 * Determine if guest voting is allowed.
	 *
	 * @return mixed|void
	 */
	public function is_guest_voting_allowed() {
		return apply_filters( 'hmn_cp_allow_guest_voting', $this->allow_guest_voting );
	}

	/**
	 * Determine if negative comment weight is allowed
	 *
	 * @return mixed|void
	 */
	public function is_negative_comment_weight_allowed() {
		return apply_filters( 'hmn_cp_allow_negative_comment_weight', $this->allow_negative_comment_weight );
	}
}
