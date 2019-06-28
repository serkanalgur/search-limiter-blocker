<?php

/*
Plugin Name: Search Limiter & Blocker
Plugin URI: http://www.wpadami.com/
Description: Set and limit search count of visitors. Also, you can block visitor IP.
Version: 1.1
Author: Serkan Algur
Author URI: http://www.wpadami.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


class SearchLimiterBlocker {
	private $search_limiter_blocker_options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'search_limiter_blocker_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'search_limiter_blocker_page_init' ) );
		add_filter( 'pre_get_posts', array( $this, 'search_visitor_ip_block' ) );
	}

	public function search_limiter_blocker_add_plugin_page() {
		add_options_page(
			'Search Limiter & Blocker', // page_title
			'Search Limiter & Blocker', // menu_title
			'manage_options', // capability
			'search-limiter-blocker', // menu_slug
			array( $this, 'search_limiter_blocker_create_admin_page' ) // function
		);
	}

	public function search_limiter_blocker_create_admin_page() {
		$this->search_limiter_blocker_options = get_option( 'search_limiter_blocker_option_name' ); ?>

		<div class="wrap">
			<h2>Search Limiter & Blocker</h2>
			<p></p>
				<?php settings_errors(); ?>

			<form method="post" action="options.php">
					<?php
					settings_fields( 'search_limiter_blocker_option_group' );
					do_settings_sections( 'search-limiter-blocker-admin' );
					submit_button();
					?>
			</form>
		</div>
			<?php
	}

	public function search_limiter_blocker_page_init() {
		register_setting(
			'search_limiter_blocker_option_group', // option_group
			'search_limiter_blocker_option_name', // option_name
			array( $this, 'search_limiter_blocker_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'search_limiter_blocker_setting_section', // id
			'Settings', // title
			array( $this, 'search_limiter_blocker_section_info' ), // callback
			'search-limiter-blocker-admin' // page
		);

		add_settings_field(
			'search_limit_count_0', // id
			'Search Limit (Count)', // title
			array( $this, 'search_limit_count_0_callback' ), // callback
			'search-limiter-blocker-admin', // page
			'search_limiter_blocker_setting_section' // section
		);

		add_settings_field(
			'block_time_in_seconds_1', // id
			'Block Time (in seconds)', // title
			array( $this, 'block_time_in_seconds_1_callback' ), // callback
			'search-limiter-blocker-admin', // page
			'search_limiter_blocker_setting_section' // section
		);

		add_settings_field(
			'message_for_blocked_visitor_2', // id
			'Message for blocked visitor', // title
			array( $this, 'message_for_blocked_visitor_2_callback' ), // callback
			'search-limiter-blocker-admin', // page
			'search_limiter_blocker_setting_section' // section
		);
	}

	public function search_limiter_blocker_sanitize( $input ) {
		$sanitary_values = array();
		if ( isset( $input['search_limit_count_0'] ) ) {
			$sanitary_values['search_limit_count_0'] = sanitize_text_field( $input['search_limit_count_0'] );
		}

		if ( isset( $input['block_time_in_seconds_1'] ) ) {
			$sanitary_values['block_time_in_seconds_1'] = sanitize_text_field( $input['block_time_in_seconds_1'] );
		}

		if ( isset( $input['message_for_blocked_visitor_2'] ) ) {
			$sanitary_values['message_for_blocked_visitor_2'] = sanitize_text_field( $input['message_for_blocked_visitor_2'] );
		}

		return $sanitary_values;
	}

	public function search_limiter_blocker_section_info() {

	}

	public function search_limit_count_0_callback() {
		printf(
			'<input class="regular-text" type="text" name="search_limiter_blocker_option_name[search_limit_count_0]" id="search_limit_count_0" value="%s">',
			isset( $this->search_limiter_blocker_options['search_limit_count_0'] ) ? esc_attr( $this->search_limiter_blocker_options['search_limit_count_0'] ) : ''
		);
	}

	public function block_time_in_seconds_1_callback() {
		printf(
			'<input class="regular-text" type="text" name="search_limiter_blocker_option_name[block_time_in_seconds_1]" id="block_time_in_seconds_1" value="%s">',
			isset( $this->search_limiter_blocker_options['block_time_in_seconds_1'] ) ? esc_attr( $this->search_limiter_blocker_options['block_time_in_seconds_1'] ) : ''
		);
	}

	public function message_for_blocked_visitor_2_callback() {
		printf(
			'<input class="regular-text" type="text" name="search_limiter_blocker_option_name[message_for_blocked_visitor_2]" id="message_for_blocked_visitor_2" value="%s">',
			isset( $this->search_limiter_blocker_options['message_for_blocked_visitor_2'] ) ? esc_attr( $this->search_limiter_blocker_options['message_for_blocked_visitor_2'] ) : ''
		);
	}

	public function get_the_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	public function search_visitor_ip_block( $query ) {

		$search_limiter_blocker_options = get_option( 'search_limiter_blocker_option_name' ); // Array of All Options
		$search_limit_count_0           = $search_limiter_blocker_options['search_limit_count_0']; // Search Limit (Count)
		$block_time_in_seconds_1        = $search_limiter_blocker_options['block_time_in_seconds_1']; // Block Time (in seconds)
		$message_for_blocked_visitor_2  = $search_limiter_blocker_options['message_for_blocked_visitor_2']; // Message for blocked visitor
		$time_for_block                 = ( $block_time_in_seconds_1 ? $block_time_in_seconds_1 : 30 );

		// Visitor search limit
		$visitor_i_p_limit = $search_limit_count_0 ? $search_limit_count_0 : 20;

		$visitor_i_p_count_r = get_option( 'visitor_ip_count-' . $this->get_the_user_ip() );
		$visitor_i_p_count   = $visitor_i_p_count_r ? $visitor_i_p_count_r : 0;

		// Check or timing
		$is_block     = get_transient( 'visitor_ip_block' . $this->get_the_user_ip() );
		$will_deleted = get_option( 'visitor_ip_block' . $this->get_the_user_ip() . '-deleteafter30min' );

		if ( preg_match( '/google|yandex|yndx|spider|bot|slurp|msn|bing|adsbot|AdIdxBot|search|face|baidu|duck|sogou|youdao|ccbot|alexa|microsoft/i', gethostbyaddr( $this->get_the_user_ip() ) ) ) {
			// Search Bots Excluded by default
			return $query;
		} else {

			// Check for block
			if ( 'blocked' === $is_block ) :
				// Kill The Proccess
				wp_die( esc_html( $message_for_blocked_visitor_2 ), 'You are Blocked By Search Limiter & Blocker for ' . esc_html( $time_for_block ) . ' seconds', 403 );
				else :
					// Not Blocked. Continue as normal
					if ( $query->is_search ) :
						if ( ( $visitor_i_p_count >= $visitor_i_p_limit ) && 'yes' === $will_deleted ) :
							delete_option( 'visitor_ip_count-' . $this->get_the_user_ip() );
							delete_option( 'visitor_ip_block' . $this->get_the_user_ip() . '-deleteafter30min' );

							// Check for limit again and add one more.
						elseif ( $visitor_i_p_count < $visitor_i_p_limit ) :
							$visitor_i_p_count++;
							update_option( 'visitor_ip_count-' . $this->get_the_user_ip(), $visitor_i_p_count );
							return $query;
						else :
							// Add option for block
							set_transient( 'visitor_ip_block' . $this->get_the_user_ip(), 'blocked', $time_for_block );
							update_option( 'visitor_ip_block' . $this->get_the_user_ip() . '-deleteafter30min', 'yes' );

							// Kill The Proccess
							wp_die( esc_html( $message_for_blocked_visitor_2 ), 'You are Blocked By Search Limiter & Blocker for ' . esc_html( $time_for_block ) . ' seconds', 403 );
						endif;
					endif;
				endif;
		}
	}

}
$search_limiter_blocker = new SearchLimiterBlocker();
