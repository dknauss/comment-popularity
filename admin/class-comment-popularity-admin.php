<?php namespace CommentPopularity;

use CommentPopularity\HMN_Comment_Popularity;

/**
 * Class HMN_Comment_Popularity_Admin
 */
class HMN_Comment_Popularity_Admin {

	private static $instance;

	private function __construct() {

		add_action( 'show_user_profile', array( $this, 'render_user_karma_field' ) );
		add_action( 'edit_user_profile', array( $this, 'render_user_karma_field' ) );

		add_action( 'personal_options_update', array( $this, 'save_user_meta' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_meta' ) );

		add_filter( 'manage_edit-comments_columns', array( $this, 'add_comment_columns' ) );
		add_filter( 'manage_comments_custom_column', array( $this, 'populate_comment_column' ), 10, 2 );

		add_filter( 'manage_edit-comments_sortable_columns', array( $this, 'make_weight_column_sortable' ) );

		add_action( 'admin_init', array( $this, 'register_plugin_settings' ) );

		add_filter( 'manage_users_columns', array( $this, 'add_users_columns' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'populate_users_columns' ), 10, 3 );
		add_filter( 'manage_users_sortable_columns', array( $this, 'make_karma_column_sortable' ) );
	}

	public static function get_instance() {

		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();

		}

		return self::$instance;
	}

	/**
	 * Adds a setting field on the Discussion admin page.
	 */
	public function register_plugin_settings() {

		register_setting( 'discussion', 'comment_popularity_prefs', array( $this, 'validate_settings' ) );

		add_settings_field( 'hmn_cp_expert_karma_field', __( 'Default karma value for expert users', 'comment-popularity' ), array( $this, 'render_expert_karma_input' ), 'discussion', 'default', array( 'label_for' => 'hmn_cp_expert_karma_field' ) );
		add_settings_field( 'hmn_cp_comment_ranking_mode', __( 'Comment ranking mode', 'comment-popularity' ), array( $this, 'render_ranking_mode_input' ), 'discussion', 'default', array( 'label_for' => 'hmn_cp_comment_ranking_mode' ) );
	}

	/**
	 * Callback to render the option HTML input on the settings page.
	 */
	public function render_expert_karma_input() {

		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			$prefs   = get_blog_option(
				$blog_id,
				'comment_popularity_prefs',
				array(
					'default_expert_karma' => 0,
					'ranking_mode'         => 'karma',
				)
			);
		} else {
			$prefs = get_option(
				'comment_popularity_prefs',
				array(
					'default_expert_karma' => 0,
					'ranking_mode'         => 'karma',
				)
			);
		}

		$default_expert_karma = array_key_exists( 'default_expert_karma', $prefs ) ? $prefs['default_expert_karma'] : 0;

		printf(
			'<input class="small-text" id="default_expert_karma" name="comment_popularity_prefs[default_expert_karma]" placeholder="%1$s" type="number" min="0" max="" step="1" value="%2$s" />',
			esc_attr__( 'Enter value', 'comment-popularity' ),
			esc_attr( $default_expert_karma )
		);
	}

	/**
	 * Callback to render ranking mode select input.
	 */
	public function render_ranking_mode_input() {

		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			$prefs   = get_blog_option(
				$blog_id,
				'comment_popularity_prefs',
				array(
					'default_expert_karma' => 0,
					'ranking_mode'         => 'karma',
				)
			);
		} else {
			$prefs = get_option(
				'comment_popularity_prefs',
				array(
					'default_expert_karma' => 0,
					'ranking_mode'         => 'karma',
				)
			);
		}

		$ranking_mode = array_key_exists( 'ranking_mode', $prefs ) ? $prefs['ranking_mode'] : 'karma';
		?>
		<select id="hmn_cp_comment_ranking_mode" name="comment_popularity_prefs[ranking_mode]">
			<option value="karma" <?php selected( $ranking_mode, 'karma' ); ?>><?php esc_html_e( 'Karma (current behavior)', 'comment-popularity' ); ?></option>
			<option value="wilson" <?php selected( $ranking_mode, 'wilson' ); ?>><?php esc_html_e( 'Wilson score lower bound', 'comment-popularity' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Wilson score uses upvote/downvote confidence and is better for low-vote comments.', 'comment-popularity' ); ?></p>
		<?php
	}

	/**
	 * Sanitize the user input.
	 *
	 * @param $input
	 *
	 * @return mixed
	 */
	public function validate_settings( $input ) {

		$valid = array();

		$valid['default_expert_karma'] = isset( $input['default_expert_karma'] ) ? absint( $input['default_expert_karma'] ) : 0;
		$valid['ranking_mode']         = ( isset( $input['ranking_mode'] ) && in_array( $input['ranking_mode'], array( 'karma', 'wilson' ), true ) ) ? $input['ranking_mode'] : 'karma';

		return $valid;
	}


	/**
	 * Renders the HTML form element for setting the user karma value.
	 *
	 * @param $user
	 */
	public function render_user_karma_field( $user ) {

		if ( ! current_user_can( 'manage_user_karma_settings' ) ) {
			return;
		}

		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			$prefs   = get_blog_option( $blog_id, 'comment_popularity_prefs', array( 'default_expert_karma' => 0 ) );
		} else {
			$prefs = get_option( 'comment_popularity_prefs', array( 'default_expert_karma' => 0 ) );
		}

		$default_karma = $prefs['default_expert_karma'];

		$current_karma = get_user_option( 'hmn_user_karma', $user->ID );

		$user_karma = empty( $current_karma ) ? $default_karma : $current_karma;

		$user_expert_status = get_user_option( 'hmn_user_expert_status', $user->ID );

		?>

		<?php wp_nonce_field( 'hmn_cp_user_meta', 'hmn_cp_user_meta_nonce' ); ?>

		<h3><?php esc_html_e( 'Comment popularity settings', 'comment-popularity' ); ?></h3>

		<table class="form-table">

			<tr>

				<th>

					<label for="hmn_user_expert_status"><?php esc_html_e( 'Expert Commenter', 'comment-popularity' ); ?></label>

				</th>

				<td>

					<input id="hmn_user_expert_status" name="hmn_user_expert_status" type="hidden" value="0" />
					<input id="hmn_user_expert_status" name="hmn_user_expert_status" type="checkbox" value="1" <?php checked( $user_expert_status ); ?> />

				</td>

			</tr>

			<tr>

				<th>

					<label for="hmn_user_karma"><?php esc_html_e( 'Karma', 'comment-popularity' ); ?></label>

				</th>

				<td>

					<input name="hmn_user_karma" type="number" step="1" min="0" id="hmn_user_karma" value="<?php echo esc_attr( $user_karma ); ?>" class="small-text">

				</td>

			</tr>

		</table>

		<?php
	}

	/**
	 * Add comment karma column to the admin view.
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public function add_comment_columns( $columns ) {

		return array_merge(
			$columns,
			array(
				'comment_karma' => __( 'Weight', 'comment-popularity' ),
			)
		);
	}

	/**
	 * Populate the custom comment list table view with karma.
	 *
	 * @param $column
	 * @param $comment_ID
	 */
	public function populate_comment_column( $column, $comment_ID ) {

		if ( 'comment_karma' !== $column ) {
			return;
		}

		$comment = get_comment( $comment_ID );

		echo (int) $comment->comment_karma;
	}

	/**
	 * Adds columns to the admin users screen.
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public function add_users_columns( $columns ) {

		return array_merge(
			$columns,
			array(
				'user_karma' => __( 'Karma', 'comment-popularity' ),
			)
		);
	}

	/**
	 * Display values for the user karma column.
	 *
	 * @param $output
	 * @param $column_name
	 * @param $user_id
	 *
	 * @return string
	 */
	public function populate_users_columns( $output, $column_name, $user_id ) {

		if ( 'user_karma' !== $column_name ) {
			return $output;
		}

		return get_user_option( 'hmn_user_karma', $user_id );
	}

	/**
	 * Add ability to sort by user karma on the users list admin view.
	 *
	 * @param $columns
	 */
	public function make_karma_column_sortable( $columns ) {

		$columns['user_karma'] = 'user_karma';

		return $columns;
	}

	/**
	 * Add ability to sort by comment weight on the edit comments admin view.
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function make_weight_column_sortable( $columns ) {

		$columns['comment_karma'] = 'comment_karma';

		return $columns;
	}
	/**
	 * Saves the custom user meta data.
	 *
	 * @param $user_id
	 */
	public function save_user_meta( $user_id ) {

		if ( ! current_user_can( 'manage_user_karma_settings' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		if ( ! isset( $_POST['hmn_cp_user_meta_nonce'] ) ) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['hmn_cp_user_meta_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'hmn_cp_user_meta' ) ) {
			return false;
		}

		$user_karma = 0;
		if ( isset( $_POST['hmn_user_karma'] ) ) {
			$user_karma = absint( wp_unslash( $_POST['hmn_user_karma'] ) );
		}

		$user_expert_status = false;
		if ( isset( $_POST['hmn_user_expert_status'] ) ) {
			$user_expert_status = (bool) absint( wp_unslash( $_POST['hmn_user_expert_status'] ) );
		}

		update_user_option( $user_id, 'hmn_user_karma', $user_karma );

		update_user_option( $user_id, 'hmn_user_expert_status', $user_expert_status );
	}
}
