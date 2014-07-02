<?php
/*
Plugin Name: TokenToMe
Description: Get access token from Twitter
Author: Julien Maury
Author URI: https://tweetpressfr.github.io
Version 1.1
*/

if (!class_exists('TokenToMe'))
	{
	class TokenToMe

		{
		public $consumer_key;
		protected $consumer_secret;
		public $screen_name;
		public $request;
		public $params = array();
		public $cache;

		public function __construct($consumer_key = false, $consumer_secret = false, $request = 'users/show', $params = array(), $screen_name = 'TweetPressFr', $cache = 900)
			{
			$this->consumer_key = $consumer_key;
			$this->consumer_secret = $consumer_secret;
			$this->screen_name = $screen_name;
			$this->request = $request;
			$this->params = $params;
			$this->cache = $cache;
			
			if (!$consumer_key || !$consumer_secret || $cache < 900) 
				return;
			}

		/*
		* Get access token from Twitter API 1.1
		* returns $access_token
		*/
		protected function get_access_token()
			{
			$credentials = $this->consumer_key . ':' . $this->consumer_secret;
			$auth = base64_encode($credentials);
			$args = array(
				'method' => 'POST',
				'httpversion' => '1.1',
				'headers' => array(
					'Authorization' => 'Basic ' . $auth,
					'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'

					// !important

				) ,
				'body' => array(
					'grant_type' => 'client_credentials'
				)
			);
			$call = wp_remote_post('https://api.twitter.com/oauth2/token', $args);
			$keys = json_decode(wp_remote_retrieve_body($call));
			$access_token = (property_exists($keys, 'access_token')) ? $keys->access_token : 'The Twitter API said no !';
			return $access_token;
			}

		/*
		* Get object from Twitter API 1.1 with the $access_token
		* returns $obj from Twitter
		*/
		protected function get_obj()
			{

			$args = array(
				'httpversion' => '1.1',
				'headers' => array(
					'Authorization' => "Bearer {$this->get_access_token() }"
				)
			);
			
			$defaults = array(
				'screen_name' => $this->screen_name
			);
			
			$q = "https://api.twitter.com/1.1/{$this->request}.json";
			$sets = wp_parse_args( $this->params, $defaults );
			$query = add_query_arg( $sets, $q);
			
			$call = wp_remote_get($query, $args);
			$obj = json_decode(wp_remote_retrieve_body($call), true); //associative array

			
			return $obj;
			}
			
		/*
		* Get infos but make sure there's some cache
		* returns (object) $infos from Twitter
		*/
		public function get_infos()
			{
			
			$cached = get_site_transient($this->screen_name.'_ttm_transient');
			
			if( false === $cached ) 
				{
				$cached = $this->get_obj();
				set_site_transient($this->screen_name.'_ttm_transient', $cached, $this->cache);//900 by default because Twitter says every 15 minutes in its doc
				}
				
			return $cached;
			}
		
		/*
		* Delete cache
		* In case you need to delete transient
		*/
		protected function delete_cache()
			{
				delete_site_transient($this->screen_name.'_ttm_transient');
			}
			
		}
	}