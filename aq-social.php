<?php
/*
Plugin Name: Aqua Social
Plugin URI: http://aquagraphite.com/
Description: Custom social icons with counter and transient caching
Version: 0.1
Author: Syamil MJ
Author URI: http://aquagraphite.com/
*/

/**
 * Copyright (c) March 2013 Syamil MJ. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

/**
 * Main Class
 *
 */
class AQ_Social {

	var $timeout  = '3600'; // no of seconds for transients to expire
	var $twitter  = 'http://urls.api.twitter.com/1/urls/count.json?url=';
	var $facebook = 'http://graph.facebook.com/';
	var $linkedin = 'http://www.linkedin.com/countserv/count/share?url=';
	var $google   = 'https://clients6.google.com/rpc';
	var $api;
	var $link;
	var $title;
	var $url;
	var $flush = false;
	var $version; //version no
	var $post_id;

	function __construct() {

		$this->version = $this->get_version();
		add_action( 'init', array(&$this, 'enqueue') );
		add_action( 'wp_ajax_aq_social_get_counts', array($this, 'ajax_get_counts') );
		add_action( 'wp_ajax_nopriv_aq_social_get_counts', array($this, 'ajax_get_counts') );

	}

	// Enqueue style/scripts
	function enqueue() {

		if(!is_admin()) {

			wp_register_style( 'aq-social', plugin_dir_url(__FILE__) . 'aq-social.css', array(), $this->version, 'all');
			wp_register_script( 'aq-social', plugin_dir_url(__FILE__) . 'aq-social.js', array('jquery'), $this->version, true );

			wp_enqueue_style('aq-social');
			wp_enqueue_script('aq-social');

		}

	}

	function init($post_id) {

		$this->link   	= get_permalink( $post_id );
		$this->title  	= get_the_title( $post_id );
		$this->post_id 	= $post_id;

		$params = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' )
		);	
		wp_localize_script( 'aq-social', 'aqvars', $params );

		return $this->html();

	}

	/** Get plugin's version no */
	function get_version() {

		if ( ! function_exists( 'get_plugins' ) )
        	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        $plugin = get_plugin_data(__FILE__);
        $version = $plugin['Version'];

        return $version;

	}

	/**
	 * Creates unique id for the transient
	 */
	function make_id($post_id, $type) {
		return "_aq_{$type}_transient_{$post_id}";
	}

	/** 
	 * asynchronous get_counts()
	 * @return array() 
	 */
	function ajax_get_counts() {

		// security check
		$nonce = $_POST['security'];
		if (! wp_verify_nonce($nonce, 'aq-social-get-counts') ) die(-1);

		$post_id 		= $_POST['post_id'];
		$this->post_id 	= $post_id;
		$this->link 	= get_permalink( $post_id );

		$tw = $this->get_counts('twitter');
		$fb = $this->get_counts('facebook');
		$li = $this->get_counts('linkedin');
		$gp = $this->get_counts('google');

		$response = array(
			'twitter' => $tw, 
			'facebook' => $fb,
			'linkedin' => $li, 
			'googleplus' => $gp
		);

		echo json_encode($response);

		die();

	}

	function get_counts($type) {

		$transient = $this->make_id($this->post_id, $type);

		// check if transients already exist
		if(false === $count = get_transient($transient)) {

			$method = "_{$type}_get_counts";
			$url    = $this->$type . $this->link;
			$count  = $this->$method($url);

			$count = $count ? $count : 0;

			//set transient
			set_transient( $transient, $count, $this->timeout );

		}

		if( $this->flush == true ) delete_transient( $transient );

		return $count;

	}

	function _twitter_get_counts($url) {

		$args = array(
			'timeout' => '30'
		);

		$response = wp_remote_get($url, $args);

		if (!is_wp_error($response) && ($response['response']['code'] == 200)) {
			$json = json_decode($response['body']);
			$count = $json->count;
			return $count;
		} else {
			return false; // make sure transients not updated if received error
		}

	}

	function _facebook_get_counts($url) {

		$args = array(
			'timeout' => '30'
		);

		$response = wp_remote_get($url, $args);

		if (!is_wp_error($response) && ($response['response']['code'] == 200)) {
			$json = json_decode($response['body']);
			$count = $json->shares;
			return $count;
		} elseif($response['response']['code'] == 400) {
			return 0;
		} else {
			return false;
		}

	}

	function _linkedin_get_counts($url) {

		$args = array(
			'timeout' => '30'
		);

		$response = wp_remote_get($url . '&format=json', $args);
		
		if (!is_wp_error($response) && ($response['response']['code'] == 200)) {
			$json = json_decode($response['body']);
			$count = $json->count;
			return $count;
		} else {
			return false;
		}

	}

	function _google_get_counts($url) {

		$fields = array(
			'method'	=> 'pos.plusones.get',
			'id'		=> 'p',
			'params'	=> array(
				'nolog'		=> true,
				'id'		=> $this->link,
				'source'	=>'widget',
				'userId'	=> '@viewer',
				'groupId'	=> '@self'
			),
			'jsonrpc'	=> '2.0',
			'key'		=> 'p',
			'apiVersion'=> 'v1'
		);

		$args = array(
			'timeout' 	=> '30',
			'headers'   => array(
				'Content-type' => 'application/json'
			),
			'body'	  	=> json_encode($fields),
		);

		$response = wp_remote_post($this->google, $args);

		if (!is_wp_error($response) && ($response['response']['code'] == 200)) {
			$json = json_decode($response['body']);
			$count = $json->result->metadata->globalCounts->count;
			return $count;
		} else {
			return false;
		}

	}

	/** HTML output */
	function html() {

		$tail = "'height=320, width=640, toolbar=no, menubar=no, scrollbars=no, resizable=no, location=no, directories=no, status=no'); return false;";
		$tw_onclick = "window.open('https://twitter.com/intent/tweet?original_referer={$this->link}&text={$this->title}-{$this->link}', 'tweet', {$tail}";
		$fb_onclick = "window.open('http://www.facebook.com/sharer/sharer.php?u={$this->link}&t={$this->title}', 'facebook_share', {$tail}";
		$li_onclick = "window.open('https://www.linkedin.com/cws/share?url={$this->link}&title={$this->title}', 'linkedin share', {$tail}";
		$gp_onclick = "window.open('https://plus.google.com/share?url={$this->link}', 'google plus', {$tail}";

		$output  = '<div id="aq-social-buttons-'.$this->post_id.'" class="aq-social-buttons" data-post_id="'.$this->post_id.'">';

			$output .= '<input class="aq-social-nonce" type="hidden" value="'. wp_create_nonce('aq-social-get-counts') .'"/>';

			$output .= '<div class="social-button social-button-twitter"><a href="#" onclick="'.$tw_onclick .'">Tweet</a> <span class="social-count">&nbsp;</span></div>';
			$output .= '<div class="social-button social-button-facebook"><a href="#" onclick="'.$fb_onclick .'">FB Share</a> <span class="social-count">&nbsp;</span></div>';
			$output .= '<div class="social-button social-button-linkedin"><a href="#" onclick="'.$li_onclick .'">LinkedIn</a> <span class="social-count">&nbsp;</span></div>';
			$output .= '<div class="social-button social-button-googleplus"><a href="#" onclick="'.$gp_onclick .'">Google+</a> <span class="social-count">&nbsp;</span></div>';

		$output .= '</div>';

		return $output;

	}

}

/** Instantiates the class */
$aq_social = new AQ_Social;

/** Function to display social counts */
function aq_social_buttons($post_id) {
	global $aq_social;
	return $aq_social->init($post_id);
}


