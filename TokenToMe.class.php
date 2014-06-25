<?php

if( ! class_exists('TokenToMe') ) {	
	
	class TokenToMe{

		public $consumer_key;
		protected $consumer_secret;

		public function __construct( $consumer_key, $consumer_secret ) 
		{

			$this->consumer_key 	= $consumer_key;
			$this->consumer_secret  = $consumer_secret;
			
		}


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

			if( $keys && !is_null( $keys ) ) $access_token = $keys->access_token;

			return $access_token;
			
		}

	}
}