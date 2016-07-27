<?php
/**
 * My Form-List Widget
 */

// Exit if accessed directly
defined( 'ABSPATH' ) or exit;

/**
 * Give Form widget
 */
class Give_Form_List_Widget extends WP_Widget
{
	/**
	 * The widget class name
	 *
	 * @var string
	 */
	protected $self;

	/**
	 * Instantiate the class
	 */
	public function __construct()
	{
		$this->self = get_class( $this );

		parent::__construct(
			strtolower( $this->self ),
			__( 'Give - Donation Form List
			', 'give' ),
			array(
				'description' => __( 'Display a list of Give Donation Forms in your theme\'s widget powered sidebar.', 'give' )
			)
		);

		add_action( 'widgets_init',          array( $this, 'widget_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_widget_scripts' ) );
	}

	/**
	 * Load widget assets only on the widget page
	 *
	 * @param string $hook
	 *
	 * @return void
	 */
	public function admin_widget_scripts( $hook )
	{
		// Directories of assets
		$js_dir     = GIVE_PLUGIN_URL . 'assets/js/admin/';
		$js_plugins = GIVE_PLUGIN_URL . 'assets/js/plugins/';
		$css_dir    = GIVE_PLUGIN_URL . 'assets/css/';

		// Use minified libraries if SCRIPT_DEBUG is turned off
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// Widget Script
		if ( $hook == 'widgets.php' ) {

			wp_enqueue_style( 'give-qtip-css', $css_dir . 'jquery.qtip' . $suffix . '.css' );

			wp_enqueue_script( 'give-qtip', $js_plugins . 'jquery.qtip' . $suffix . '.js', array( 'jquery' ), GIVE_VERSION );

			wp_enqueue_script( 'give-admin-widgets-scripts', $js_dir . 'admin-widgets' . $suffix . '.js', array( 'jquery' ), GIVE_VERSION, false );
		}
	}

	/**
	 * Echo the widget content.
	 *
	 * @param array $args     Display arguments including before_title, after_title,
	 *                        before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance )
	{
		extract( $args );

		$title = !empty( $instance['title'] ) ? $instance['title'] : '';
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		$sort = !empty( $instance['sort']) ? $instance['sort'] : 'date';
		$order = 'DESC';
		if ($sort == 'title') {
			$order = 'ASC';
		}
		echo $before_widget;

		do_action( 'give_before_forms_widget' );

		echo $title ? $before_title . $title . $after_title : '';

		//give_get_donation_form( $instance );
		$my_args = array(
			'post_type'      => 'give_forms',
			'post_status'    => 'publish',
			'tax_query'     => array(
				array(
					'taxonomy'  => 'give_forms_category',
					'field'     => 'slug',
					'terms'     => 'active'
				)
			),
			'numberposts'		=> 5,
			'orderby'			=> $sort,
			'order'				=> $order
		);
		if ($sort = 'meta_value_num') {
			$my_args['meta_key'] = '_give_form_earnings';
		}
		$give_forms = get_posts( $my_args );
		echo "<div>";
		foreach ( $give_forms as $give_form ) {
			//echo get_permalink($give_form->ID );
		echo "<a href = \"".esc_url(get_post_permalink($give_form->ID ))."\">";
		echo get_the_title($give_form->ID) . " - Joined " . get_the_date("F j",$give_form->ID) . "<br>";
		echo "$".get_post_meta($give_form->ID,'_give_form_earnings',true)." raised</a><br>";
	    }
		echo "</div>";
		echo $after_widget;

		do_action( 'give_after_forms_widget' );
	}

	/**
	 * Output the settings update form.
	 *
	 * @param array $instance Current settings.
	 *
	 * @return string
	 */
	public function form( $instance )
	{
		$defaults = array(
			'title'        => '',
			'id'           => '',
			'float_labels' => '',
			'sort'		   => 'date',
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		extract( $instance );

		// Query Give Forms

		$args = array(
			'post_type'      => 'give_forms',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);

		$give_forms = get_posts( $args );

		// Widget: Title

		?><p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'give' ); ?></label>
		<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" /><br>
		<small><?php _e( 'Leave blank to hide the widget title.', 'give' ); ?></small>
		</p>
		<select id="<?php echo $this->get_field_id('sort') ?>" name="<?php echo $this->get_field_name('sort') ?>">
			<option  value='date' <?php echo ($instance['sort'] == 'date')? 'selected="selected"' : "";?>>Date</option>
			<option value="title" <?php echo ($instance['sort'] == 'title')? 'selected="selected"' : "";?>>Title</option>
			<option value='meta_value_num' <?php echo ($instance['sort'] == 'meta_value_num')? 'selected="selected"' : "";?>>Amount Raised</option>
		</select><?php

		// Widget: Give Form

		?><p>
		<?php /*foreach ( $give_forms as $give_form ) { ?>
		<a href="<?php get_permalink($give_form->ID ); ?>"><?php echo $give_form->post_title; ?></a>
	<?php } ?>
		</p><?php */

		// Widget: Floating Labels

		?><?php
	}

	/**
	 * Register the widget
	 *
	 * @return void
	 */
	function widget_init()
	{
		register_widget( $this->self );
	}

	/**
	 * Update the widget
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance )
	{
		$this->flush_widget_cache();

		return $new_instance;
	}

	/**
	 * Flush widget cache
	 *
	 * @return void
	 */
	public function flush_widget_cache()
	{
		wp_cache_delete( $this->self, 'widget' );
	}
}

new Give_Form_List_Widget;


/*----------------------------------------------------------------------*/
/**Recent donations widget*/
class Give_Donations_Widget extends WP_Widget
{
	/**
	 * The widget class name
	 *
	 * @var string
	 */
	protected $self;

	/**
	 * Instantiate the class
	 */
	public function __construct()
	{
		$this->self = get_class( $this );

		parent::__construct(
			strtolower( $this->self ),
			__( 'Give - Recent Donations
			', 'give' ),
			array(
				'description' => __( 'Display a list of the most recent donations in your theme\'s widget powered sidebar.', 'give' )
			)
		);

		add_action( 'widgets_init',          array( $this, 'widget_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_widget_scripts' ) );
	}

	/**
	 * Load widget assets only on the widget page
	 *
	 * @param string $hook
	 *
	 * @return void
	 */
	public function admin_widget_scripts( $hook )
	{
		// Directories of assets
		$js_dir     = GIVE_PLUGIN_URL . 'assets/js/admin/';
		$js_plugins = GIVE_PLUGIN_URL . 'assets/js/plugins/';
		$css_dir    = GIVE_PLUGIN_URL . 'assets/css/';

		// Use minified libraries if SCRIPT_DEBUG is turned off
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// Widget Script
		if ( $hook == 'widgets.php' ) {

			wp_enqueue_style( 'give-qtip-css', $css_dir . 'jquery.qtip' . $suffix . '.css' );

			wp_enqueue_script( 'give-qtip', $js_plugins . 'jquery.qtip' . $suffix . '.js', array( 'jquery' ), GIVE_VERSION );

			wp_enqueue_script( 'give-admin-widgets-scripts', $js_dir . 'admin-widgets' . $suffix . '.js', array( 'jquery' ), GIVE_VERSION, false );
		}
	}

	/**
	 * Echo the widget content.
	 *
	 * @param array $args     Display arguments including before_title, after_title,
	 *                        before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance )
	{
		extract( $args );

		$title = !empty( $instance['title'] ) ? $instance['title'] : '';
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo $before_widget;

		//do_action( 'give_before_forms_widget' );

		echo $title ? $before_title . $title . $after_title : '';

		//give_get_donation_form( $instance );
		$my_args = array(
			'post_type'      => 'give_payment',
			'numberposts'	 => 4
		);
		$donations = get_posts( $my_args );
		echo "<div>";
		if (count($donations) == 0) {
			echo "<div>Be the first to donate!</div>";
		}
		foreach ( $donations as $don ) {
			$don_meta = give_get_payment_meta($don->ID);
			$don_user_id = give_get_payment_customer_id($don->ID);
			echo "<div><strong>";
			if ($don_meta['anon'] == 'yes') {
				echo "Anonymous supported ";// get_the_title(give_get_payment_form_id($don->ID)) . ": ";
			}
			else {
				$cust = new Give_Customer($don_user_id);
				echo $cust->name . " supported "; //get_the_title(give_get_payment_form_id($don->ID)) . ": ";
			}
			if (has_term('','give_forms_tag',give_get_payment_form_id($don->ID))) {
				$term = get_the_tags(give_get_payment_form_id($don->ID),'give_forms_tag');
				echo $term->name . ": ";
			}
			else {
				echo get_the_title(give_get_payment_form_id($don->ID)) . ": ";
			}
			echo esc_html( give_currency_filter( give_format_amount( give_get_payment_amount( $don->ID ) ) ) );
			echo "</strong><br>";
			if ($don_meta['anon_to_p'] == 'yes' && $don_meta['message'] != "") {
				echo '<p style="color:grey">' . $don_meta['message'] . "</p>";
			}
			echo "</div>";
			//echo get_permalink($give_form->ID );
			//echo "<a href = \"".esc_url(get_post_permalink($give_form->ID ))."\">" .get_the_title($give_form->ID)."</a><br>";
		}
		echo "</div>";
		echo $after_widget;

		//do_action( 'give_after_forms_widget' );
	}

	/**
	 * Output the settings update form.
	 *
	 * @param array $instance Current settings.
	 *
	 * @return string
	 */
	public function form( $instance )
	{
		$defaults = array(
			'title'        => '',
			'id'           => '',
			'float_labels' => '',
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		extract( $instance );

		// Query Give Forms

		$args = array(
			'post_type'      => 'give_forms',
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
		);

		$give_forms = get_posts( $args );

		// Widget: Title

		?><p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'give' ); ?></label>
		<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" /><br>
		<small><?php _e( 'Leave blank to hide the widget title.', 'give' ); ?></small>
		</p><?php

		// Widget: Give Form
/*
		?><p>
		<?php foreach ( $give_forms as $give_form ) { ?>
		<a href="<?php get_permalink($give_form->ID ); ?>"><?php echo $give_form->post_title; ?></a>
	<?php } ?>
		</p><?php
*/
		// Widget: Floating Labels

		?><?php
	}

	/**
	 * Register the widget
	 *
	 * @return void
	 */
	function widget_init()
	{
		register_widget( $this->self );
	}

	/**
	 * Update the widget
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance )
	{
		$this->flush_widget_cache();

		return $new_instance;
	}

	/**
	 * Flush widget cache
	 *
	 * @return void
	 */
	public function flush_widget_cache()
	{
		wp_cache_delete( $this->self, 'widget' );
	}
}

new Give_Donations_Widget();

/**
 * Give Teams widget
 */
class Give_Team_List_Widget extends WP_Widget
{
	/**
	 * The widget class name
	 *
	 * @var string
	 */
	protected $self;

	/**
	 * Instantiate the class
	 */
	public function __construct()
	{
		$this->self = get_class( $this );

		parent::__construct(
			strtolower( $this->self ),
			__( 'Give - Donation Team List
			', 'give' ),
			array(
				'description' => __( 'Display a list of teams in your theme\'s widget powered sidebar.', 'give' )
			)
		);

		add_action( 'widgets_init',          array( $this, 'widget_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_widget_scripts' ) );
	}

	/**
	 * Load widget assets only on the widget page
	 *
	 * @param string $hook
	 *
	 * @return void
	 */
	public function admin_widget_scripts( $hook )
	{
		// Directories of assets
		$js_dir     = GIVE_PLUGIN_URL . 'assets/js/admin/';
		$js_plugins = GIVE_PLUGIN_URL . 'assets/js/plugins/';
		$css_dir    = GIVE_PLUGIN_URL . 'assets/css/';

		// Use minified libraries if SCRIPT_DEBUG is turned off
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// Widget Script
		if ( $hook == 'widgets.php' ) {

			wp_enqueue_style( 'give-qtip-css', $css_dir . 'jquery.qtip' . $suffix . '.css' );

			wp_enqueue_script( 'give-qtip', $js_plugins . 'jquery.qtip' . $suffix . '.js', array( 'jquery' ), GIVE_VERSION );

			wp_enqueue_script( 'give-admin-widgets-scripts', $js_dir . 'admin-widgets' . $suffix . '.js', array( 'jquery' ), GIVE_VERSION, false );
		}
	}

	/**
	 * Echo the widget content.
	 *
	 * @param array $args     Display arguments including before_title, after_title,
	 *                        before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance )
	{
		extract( $args );

		$title = !empty( $instance['title'] ) ? $instance['title'] : '';
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		echo $before_widget;

		do_action( 'give_before_team_widget' );

		echo $title ? $before_title . $title . $after_title : '';

		//give_get_donation_form( $instance );
		$teams = get_terms(array('taxonomy' => 'give_forms_tag'));
		$groups = array();
		foreach ($teams as $team) {
			$args = array(
				'post_type' => 'give_forms',
				'tax_query' => array(
					'relation' => 'AND',
					array(
						'taxonomy' => 'give_forms_tag',
						'field' => 'slug',
						'terms' => $team->slug
					),
					array(
						'taxonomy' => 'give_forms_category',
						'field' => 'slug',
						'terms' => 'active'
					)
				)
			);
			$members = get_posts($args);
			$goal = 0;
			$income = 0;
			foreach ($members as $member) {
				$goal += give_get_form_goal($member->ID);
				$income += give_get_form_earnings_stats($member->ID) + get_post_meta($member->ID, '_give_offline_money', true);
			}
			$groups[$team->name] = $income;
		}
		arsort($groups,SORT_NUMERIC);

		echo "<div>";
		$count = 0;
		foreach ( $groups as $group => $income ) {
			if ($count < 5) {
				echo $group . " - " . "$" . $income . " raised<br>";
			}
			$count++;
		}
		echo "</div>";
		echo $after_widget;

		do_action( 'give_after_team_widget' );
	}

	/**
	 * Output the settings update form.
	 *
	 * @param array $instance Current settings.
	 *
	 * @return string
	 */
	public function form( $instance )
	{
		$defaults = array(
			'title'        => '',
			'id'           => '',
			'float_labels' => '',
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		extract( $instance );


		// Widget: Title

		?><p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'give' ); ?></label>
		<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" /><br>
		<small><?php _e( 'Leave blank to hide the widget title.', 'give' ); ?></small>
		</p><?php
	}

	/**
	 * Register the widget
	 *
	 * @return void
	 */
	function widget_init()
	{
		register_widget( $this->self );
	}

	/**
	 * Update the widget
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance )
	{
		$this->flush_widget_cache();

		return $new_instance;
	}

	/**
	 * Flush widget cache
	 *
	 * @return void
	 */
	public function flush_widget_cache()
	{
		wp_cache_delete( $this->self, 'widget' );
	}
}

new Give_Team_List_Widget;
