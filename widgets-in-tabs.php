<?php
/*
 * Plugin Name: Widgets In Tabs
 * Plugin URI: http://wordpress.org/plugins/widgets-in-tabs/
 * Description: Show all the widgets you love, forget about clutter.
 * Author: Anas H. Sulaiman
 * Version: 1.2
 * Author URI: http://ahs.pw/
 * Text Domain: wit
 * Domain Path: /langs/
 * License: GPL2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

define( 'WIT_VERSION', '1.2' );

// Register WIT
add_action('widgets_init', 'register_wit');
function register_wit() {
	 register_sidebar(array(
        'id' => 'wit_area',
        'name' => __('Widgets In Tabs Area', 'wit'),
        'description'   => __('Add widgets here to show them in WIT Widget. If you put WIT widget here, bad things will happen!', 'wit'),
        'before_widget' => '<li id="%1$s" class="%2$s wit-tab-content">',
        'after_widget' => '</li>',
        'before_title' => '<h3 class="wit-tab-title">',
        'after_title' => '</h3>'
        )
	 );

	register_widget('Widgets_In_Tabs');
}
add_action( 'plugins_loaded', 'wit_load_textdomain' );
function wit_load_textdomain() {
	load_plugin_textdomain( 'wit', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
}

// WIT class
class Widgets_In_Tabs extends WP_Widget {

	function __construct() {
		parent::__construct(
			'Widgets_In_Tabs',
			__('Widgets In Tabs', 'wit'),
			array( 'description' => __( 'Show all the widgets you love, forget about clutter.', 'wit' ) )
		);

		add_shortcode( 'wit', array( $this, 'wit_shortcode' ) );
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'wit_go_to_widgets_link' ) );

		// Register assets
		if ( is_active_widget( false, false, $this->id_base ) && !is_admin()) {
			add_action( 'wp_print_styles', array( $this, 'enqueue_style' ) );
			add_action( 'wp_print_scripts', array( $this, 'enqueue_scripts' ) );
		}
		add_action('admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ));

		// Register shortcode button
		if ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) {
			add_filter( 'mce_buttons', array( $this, 'shortcode_mce_button' ) );
			add_filter( 'mce_external_plugins', array( $this, 'shortcode_mce_plugin' ) );
		}
		add_action( 'admin_print_footer_scripts', array ( $this, 'shortcode_quicktag' ) );
	}

	public function enqueue_style() {
		wp_register_style('wit', plugins_url( 'wit-all.min.css', __FILE__ ), array(), WIT_VERSION);
		wp_enqueue_style( 'wit' );
	}

	public function enqueue_scripts() {
		wp_register_script('wit', plugins_url( 'wit-all.min.js', __FILE__ ), array('jquery'), WIT_VERSION, true);

		if (!wp_script_is( 'wit', 'enqueued' )) {
			$l10n = array(
				'string_untitled' => __('Untitled', 'wit')
				);
			wp_localize_script('wit', 'WIT_L10N', $l10n);
		}

		wp_enqueue_script( 'wit' );
	}

	public function enqueue_admin_styles($hook) {
		if ('widgets.php' != $hook)
			return;
		wp_register_style('wit_admin', plugins_url( 'wit-admin.min.css', __FILE__ ), array(), WIT_VERSION);
		wp_enqueue_style( 'wit_admin' );
	}

	public function wit_go_to_widgets_link($actions) {
		return array_merge(
			array( 'settings' => sprintf( '<a href="%s">%s</a>', 'widgets.php', __( 'Go to Widgets', 'wit' ) ) ),
			$actions
		);
	}

	public function shortcode_mce_button( $buttons ) {
		array_push( $buttons, '|', 'wit_button' );
		return $buttons;
	}

	public function shortcode_mce_plugin( $plugins ) {
		$plugins['wit_button'] = plugins_url( 'wit-button.min.js', __FILE__ );
		return $plugins;
	}

	public function shortcode_quicktag() {
	    if (wp_script_is('quicktags')){
			?>
		    <script type="text/javascript">
			    QTags.addButton( 'wit_quicktag', '[wit]', '[wit]', '', 'w', 'WIT Widget' );
		    </script>
			<?php
	    }
	}

	public function wit_shortcode( $atts ) {
		// example:
		// [wit interval='3' tab_style='scroll']
		 
		$atts = shortcode_atts( $this->defaults, $atts );
		$instance = 
			"interval={$atts['interval']}".
			"&tab_style={$atts['tab_style']}".
			"&hide_effect={$atts['hide_effect']}".
			"&show_effect={$atts['show_effect']}".
			"&effect_style={$atts['effect_style']}".
			"&duration={$atts['duration']}";
		$args = array(
			'before_widget' => '<div class="widget widget_widgets_in_tabs">',
			'after_widget' => '</div>',
			'before_title' => '<h2 class="wit-title">',
			'after_title' => '</h2>'
			);
		ob_start();
		the_widget( 'Widgets_In_Tabs', $instance, $args );
		return ob_get_clean();
	}

	/**
	 * Front-end display of Widgets In Tabs.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$instance = wp_parse_args((array) $instance, $this->defaults);
		$data_string = "";
		foreach ($instance as $key => $value) {
			$data_string .= "data-$key=\"$value\" ";
		}

		$title = __('Widgets In Tabs', 'wit');

		echo $args['before_widget'];
		echo $args['before_title'] . $title . $args['after_title'];
		echo "<ul class=\"wit-tab-container\" $data_string>";
		dynamic_sidebar('wit_area');
		echo '</ul>';
		echo $args['after_widget'];
	}

	/**
	 * Back-end Widgets In Tabs form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args((array) $instance, $this->defaults);

		?>
		<p><?php _e('All widgets added to Widgets In Tabs Area will appear as tabs in place of this widget.', 'wit' ); ?></p>
		<p>
			<label for="<?php echo $this->get_field_id( 'interval' ); ?>"><?php _e( 'Animation interval in seconds: (0 = disable)' , 'wit'); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'interval' ); ?>" name="<?php echo $this->get_field_name( 'interval' ); ?>" type="text" value="<?php echo esc_attr( $instance['interval'] ); ?>" />
		</p>
		<p>
			<legend><?php _e( 'Tab style:' , 'wit'); ?></legend>
			<input id="<?php echo $this->get_field_id( 'tab_style' ) . '-1'; ?>" name="<?php echo $this->get_field_name( 'tab_style' ); ?>" type="radio" value="scroll"   <?php if ($instance['tab_style'] == 'scroll')   echo "checked"; ?>/><label for="<?php echo $this->get_field_id( 'tab_style' ) . '-1'; ?>"><?php _e('Scrollbar', 'wit') ?></label>
			<input id="<?php echo $this->get_field_id( 'tab_style' ) . '-2'; ?>" name="<?php echo $this->get_field_name( 'tab_style' ); ?>" type="radio" value="show_all" <?php if ($instance['tab_style'] == 'show_all') echo "checked"; ?>/><label for="<?php echo $this->get_field_id( 'tab_style' ) . '-2'; ?>"><?php _e('Show All', 'wit') ?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'hide_effect' ); ?>"><?php _e( 'Hide tab effect' , 'wit'); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id( 'hide_effect' ); ?>" name="<?php echo $this->get_field_name( 'hide_effect' ); ?>" >
				<?php
				foreach ($this->effects as $key => $value):
				?>
				<option value="<?php echo $key ?>" <?php if ($instance['hide_effect'] == $key) echo "selected" ?> ><?php echo $value ?></option>
				<?php
				endforeach;
				?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_effect' ); ?>"><?php _e( 'Show tab effect' , 'wit'); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id( 'show_effect' ); ?>" name="<?php echo $this->get_field_name( 'show_effect' ); ?>" >
				<?php
				foreach ($this->effects as $key => $value):
				?>
				<option value="<?php echo $key ?>" <?php if ($instance['show_effect'] == $key) echo "selected" ?> ><?php echo $value ?></option>
				<?php
				endforeach;
				?>
			</select>
		</p>
		<p>
			<legend><?php _e( 'Effect style:' , 'wit'); ?></legend>
			<input id="<?php echo $this->get_field_id( 'effect_style' ) . '-1'; ?>" name="<?php echo $this->get_field_name( 'effect_style' ); ?>" type="radio" value="prll"   <?php if ($instance['effect_style'] == 'prll')   echo "checked"; ?>/><label for="<?php echo $this->get_field_id( 'effect_style' ) . '-1'; ?>"><?php _e('Parallel', 'wit') ?></label>
			<input id="<?php echo $this->get_field_id( 'effect_style' ) . '-2'; ?>" name="<?php echo $this->get_field_name( 'effect_style' ); ?>" type="radio" value="seq" <?php if ($instance['effect_style'] == 'seq') echo "checked"; ?>/><label for="<?php echo $this->get_field_id( 'effect_style' ) . '-2'; ?>"><?php _e('Sequential', 'wit') ?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'duration' ); ?>"><?php _e( 'Effect duration in milliseconds: (0 = disable)' , 'wit'); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'duration' ); ?>" name="<?php echo $this->get_field_name( 'duration' ); ?>" type="text" value="<?php echo esc_attr( $instance['duration'] ); ?>" />
		</p>
		<?php
	}

	/**
	 * Sanitize Widgets In Tabs form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		// make sure the interval string is an integer && is greater than or equal to zero
		$intervali = (int)$new_instance['interval'];
		if (((string)$intervali == $new_instance['interval']) && $intervali >= 0)
			$instance['interval'] =  $new_instance['interval'];
		else 
			$instance['interval'] = $this->defaults['interval'];

		if ($new_instance['tab_style'] == 'show_all')
			$instance['tab_style'] = 'show_all';
		else if ($new_instance['tab_style'] == 'scroll')
			$instance['tab_style'] = 'scroll';
		else
			$instance['tab_style'] = $this->defaults['tab_style'];

		if (in_array($new_instance['hide_effect'], array_keys($this->effects)))
			$instance['hide_effect'] = $new_instance['hide_effect'];
		else
			$instance['hide_effect'] = $this->defaults['hide_effect'];

		if (in_array($new_instance['show_effect'], array_keys($this->effects)))
			$instance['show_effect'] = $new_instance['show_effect'];
		else
			$instance['show_effect'] = $this->defaults['show_effect'];

		if ($new_instance['effect_style'] == 'prll')
			$instance['effect_style'] = 'prll';
		else if ($new_instance['effect_style'] == 'seq')
			$instance['effect_style'] = 'seq';
		else
			$instance['effect_style'] = $this->defaults['effect_style'];

		// make sure the duration string is an integer && is greater than or equal to zero
		$durationi = (int)$new_instance['duration'];
		if (((string)$durationi == $new_instance['duration']) && $durationi >= 0)
			$instance['duration'] =  $new_instance['duration'];
		else 
			$instance['duration'] = $this->defaults['duration'];

		return $instance;
	}

	private $defaults = array(
			'interval' => '0',
			'tab_style' => 'scroll',
			'hide_effect' => 'slide_up',
			'show_effect' => 'slide_down',
			'effect_style' => 'prll',
			'duration' => '400'
			);
	private $effects = array(
			'blind_up' => 'Blind Up',
			'blind_down' => 'Blind Down',
			'blind_left' => 'Blind Left',
			'blind_right' => 'Blind Right',
			'blind_ver' => 'Blind Vertical',
			'blind_hor' => 'Blind Horizontal',
			'bounce' => 'Bounce',
			'clip_ver' => 'Clip Vertical',
			'clip_hor' => 'Clip Horizontal',
			'drop_up' => 'Drop Up',
			'drop_down' => 'Drop Down',
			'drop_left' => 'Drop Left',
			'drop_right' => 'Drop Right',
			'explode' => 'Explode',
			'fade' => 'Fade',
			'fold_ver_hor' => 'Fold Vertical->Horizontal',
			'fold_hor_ver' => 'Fold Horizontal->Vertical',
			'puff' => 'Puff',
			'pulsate' => 'Pulsate',
			'scale_ver' => 'Scale Vertical',
			'scale_hor' => 'Scale Horizontal',
			'scale_ver_hor' => 'Scale Vertical+Horizontal',
			'shake_left' => 'Shake Left',
			'shake_right' => 'Shake Left',
			'shake_up' => 'Shake Up',
			'shake_down' => 'Shake Down',
			'slide_up' => 'Slide Up',
			'slide_down' => 'Slide Down',
			'slide_left' => 'Slide Left',
			'slide_right' => 'Slide Right'
		);

} // class Widgets_In_Tabs
