<?php
/*
Plugin Name: Aqua Social
Plugin URI: http://syamilmj.com/
Description: Social sharing buttons
Version: 0.2
Author: Syamil MJ
Author URI: http://syamilmj.com/
*/

/**
 * Copyright (c) March 2013 Syamil MJ. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */

class AQ_Social {

	var $version;
	var $link;
	var $title;
	var $post_id;

	function __construct() {
		$this->version = $this->get_version();
		add_action( 'init', array( &$this, 'enqueue' ) );
	}

	function enqueue() {
		if ( ! is_admin() ) {
			wp_register_style( 'aq-social', plugin_dir_url( __FILE__ ) . 'aq-social.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'aq-social' );
		}
	}

	function init( $post_id ) {
		$this->link    = get_permalink( $post_id );
		$this->title   = get_the_title( $post_id );
		$this->post_id = $post_id;
		return $this->html();
	}

	function get_version() {
		if ( ! function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$plugin = get_plugin_data( __FILE__ );
		return $plugin['Version'];
	}

	function html() {
		$tail = "'height=320, width=640, toolbar=no, menubar=no, scrollbars=no, resizable=no, location=no, directories=no, status=no'); return false;";

		$tw_url = 'https://twitter.com/intent/tweet?url=' . rawurlencode( $this->link ) . '&text=' . rawurlencode( $this->title );
		$fb_url = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $this->link );
		$li_url = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $this->link );

		$tw_onclick = "window.open('" . esc_js( $tw_url ) . "', 'tweet', {$tail}";
		$fb_onclick = "window.open('" . esc_js( $fb_url ) . "', 'facebook_share', {$tail}";
		$li_onclick = "window.open('" . esc_js( $li_url ) . "', 'linkedin_share', {$tail}";

		$output  = '<div id="aq-social-buttons-' . $this->post_id . '" class="aq-social-buttons">';
		$output .= '<div class="social-button social-button-twitter"><a href="#" onclick="' . $tw_onclick . '">Tweet</a></div>';
		$output .= '<div class="social-button social-button-facebook"><a href="#" onclick="' . $fb_onclick . '">FB Share</a></div>';
		$output .= '<div class="social-button social-button-linkedin"><a href="#" onclick="' . $li_onclick . '">LinkedIn</a></div>';
		$output .= '</div>';

		return $output;
	}

}

$aq_social = new AQ_Social;

function aq_social_buttons( $post_id ) {
	global $aq_social;
	return $aq_social->init( $post_id );
}
