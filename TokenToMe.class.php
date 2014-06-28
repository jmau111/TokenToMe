<?php

if( ! class_exists('TokenToMe') ) {	
	
	class TokenToMe{

		public $consumer_key;
		protected $consumer_secret;
		public $screen_name;

		public function __construct( $consumer_key = false, $consumer_secret = false, $screen_name = 'TweetPressFr' ) 
		{

			$this->consumer_key 	= $consumer_key;
			$this->consumer_secret  = $consumer_secret;
			$this->screen_name		= $screen_name;
			
			
			if( !$consumer_key || !$consumer_secret ) 
				return;
			
		}

		/*
		* Get access token from Twitter API 1.1
		* returns $access_token
		*/
		public function get_access_token()
		{

			$credentials = $this->consumer_key . ':' . $this->consumer_secret;
			$auth        = base64_encode($credentials);


			$params = array(
				'method' 		=> 'POST',
				'httpversion'	=> '1.1',
				'blocking' 		=> true,
				'headers' 		=> array(
					'Authorization' => 'Basic ' . $auth,
					'Content-Type' 	=> 'application/x-www-form-urlencoded;charset=UTF-8'// !important
				),
				
				'body' 			=> array( 'grant_type' => 'client_credentials' )
			);

			$call 	  = wp_remote_retrieve_body( wp_remote_post('https://api.twitter.com/oauth2/token', $params) );
			$keys     = json_decode($call);

			$access_token = ( $keys && !is_null( $keys ) ) ? $keys->access_token : 'The Twitter API said no !';
			
			return $access_token;
			
		}
		
		/*
		* Get infos for user from Twitter API 1.1 with the $access_token
		* returns (object) $infos from Twitter
		*/
		
		public function get_infos() {
		
			$args = array(
				'httpversion' => '1.1',
				'blocking' => true,
				'headers' => array(
					'Authorization' => "Bearer {$this->get_access_token()}"
				)
			);
	 

			$q	 	= "https://api.twitter.com/1.1/users/show.json?screen_name={$this->screen_name}";
			$call	= wp_remote_retrieve_body( wp_remote_get($q, $args) );
			
			$infos	= json_decode($call);
			
			return var_dump($infos);
		
		}
		

	}
}