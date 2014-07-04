<?php
/*
Plugin Name: TokenToMe
Description: Get access token from Twitter
Author: Julien Maury
Author URI: https://tweetpressfr.github.io
Version 1.2
*/

if (!class_exists('TokenToMe'))
	{
	class TokenToMe

		{
		public $consumer_key;
		protected $consumer_secret;
		public $request;
		public $params = array();
		public $cache;
		public $textdomain = 'ttm';

		public function __construct($consumer_key = false, $consumer_secret = false, $request = 'users/show', $params = array(), $cache = 900)
			{
			$this->consumer_key = $consumer_key;
			$this->consumer_secret = $consumer_secret;
			$this->request = $request;
			$this->params = $params;
			$this->cache = $cache;
			
			if (!$consumer_key || !$consumer_secret || $cache < 900) 
				return;
			
			}

		/*
		* Get token from Twitter API 1.1
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
			
			// need to know what's going on before proceeding
			if( !is_wp_error($call) 
			  && isset( $call['response']['code'] )
			  && 200 == $call['response']['code'] )
				{
				$keys = json_decode(wp_remote_retrieve_body($call));
				update_option( md5($this->consumer_key.$this->consumer_secret).'_twitter_access_token', $keys->access_token );
				return __('Access granted ^^ !', $this->textdomain);
				} 
				
			else 
				{
					
				return $this->check_http_code($call['response']['code']);
				
				}
			
			}
			
			
		/*
		* Full check
		* returns $error
		*/			
		protected function check_http_code($http_code)
			{
			
			switch( $http_code )
				{

				case '400':
				case '401':
				case '403':
				case '406':
					$error = __('Your public and/or private key might be unset or incorrect or request is wrong. In any case this error is not due to Twitter API.',$this->textdomain);
					break;
					
					
				case '404':
					$error = __('Account might not exist',$this->textdomain);
					break;
				
				case '429':
					$error = __('Rate limits are exceed, take it easy!',$this->textdomain);
					break;
					
				case '500':
				case '502':
				case '503':
					$error = __('Twitter is overwhelmed or something bad happened with its API.',$this->textdomain);
					break;
					
				default:
					$error = __('Something is really wrong or missing.', $this->textdomain);		
				
				}
				
				return $error;
			
			}

		/*
		* Get object from Twitter API 1.1 with the $access_token
		* returns $obj from Twitter
		*/
		protected function get_obj()
			{
			$this->get_access_token();
			$access_token = get_option( md5($this->consumer_key.$this->consumer_secret ).'_twitter_access_token');

			$args = array(
				'httpversion' => '1.1',
				'headers' => array(
					'Authorization' => "Bearer {$access_token}"
				)
			);
			
			$defaults = array(
				'count' => 1
			);
			
			$q = "https://api.twitter.com/1.1/{$this->request}.json";
			$sets = wp_parse_args( $this->params, $defaults );
			$query = add_query_arg( $sets, $q);
			
			$call = wp_remote_get($query, $args);
			
			if( !is_wp_error($call) 
			  && isset( $call['response']['code'] )
			  && 200 == $call['response']['code'] )
				{
				$obj = json_decode(wp_remote_retrieve_body($call));
				}
			else 
				{
				$this->delete_cache();
				$obj = $this->check_http_code($call['response']['code']);
				}
			
			return apply_filters('the_twitter_object', $obj);
			}
			
		/*
		* Get infos but make sure there's some cache
		* returns (object) $infos from Twitter
		*/
		public function get_infos()
			{
			$cached = get_site_transient(substr(md5($this->request), 0, 10).'_ttm_transient');
			
			if( false === $cached ) 
				{
				$cached = $this->get_obj();
				set_site_transient(substr(md5($this->request), 0, 10).'_ttm_transient', $cached, $this->cache);//900 by default because Twitter says every 15 minutes in its doc
				}
				
			return $cached;
			}
			
		/*
		* Format obj from Twitter
		* returns $format
		*/
		public function jc_twitter_format( $raw_text, $tweet = NULL ) 
			{
			// first set output to the value we received when calling this function
			$format = $raw_text;

			// create xhtml safe text (mostly to be safe of ampersands)
			$format = htmlentities( html_entity_decode( $raw_text, ENT_NOQUOTES, 'UTF-8' ), ENT_NOQUOTES, 'UTF-8' );

			// parse urls
			if ( $tweet == NULL ) {
				// for regular strings, just create <a> tags for each url
				$pattern = '/([A-Za-z]+:\/\/[A-Za-z0-9-_]+\.[A-Za-z0-9-_:%&\?\/.=]+)/i';
				$replacement = '<a href="${1}" rel="external">${1}</a>';
				$format = preg_replace( $pattern, $replacement, $format );
			} else {
				// for tweets, let's extract the urls from the entities object
				foreach ( $tweet->entities->urls as $url ) {
					$old_url = $url->url;
					$expanded_url = ( empty( $url->expanded_url ) ) ? $url->url : $url->expanded_url;
					$display_url = ( empty( $url->display_url ) ) ? $url->url : $url->display_url;
					$replacement = '<a href="' . $expanded_url . '" rel="external">' . $display_url . '</a>';
					$format = str_replace( $old_url, $replacement, $format );
				}

				// let's extract the hashtags from the entities object
				foreach ( $tweet->entities->hashtags as $hashtags ) {
					$hashtag = '#' . $hashtags->text;
					$replacement = '<a href="http://twitter.com/search?q=%23' . $hashtags->text . '" rel="external">' . $hashtag . '</a>';
					$format = str_ireplace( $hashtag, $replacement, $format );
				}

				// let's extract the usernames from the entities object
				foreach ( $tweet->entities->user_mentions as $user_mentions ) {
					$username = '@' . $user_mentions->screen_name;
					$replacement = '<a href="http://twitter.com/' . $user_mentions->screen_name . '" rel="external" title="' . $user_mentions->name . ''.__('on Twitter',$this->textdomain).'">' . $username . '</a>';
					$format = str_ireplace( $username, $replacement, $format );
				}

				// if we have media attached, let's extract those from the entities as well
				if ( isset( $tweet->entities->media ) ) {
					foreach ( $tweet->entities->media as $media ) {
						$old_url = $media->url;
						$replacement = '<a href="' . $media->expanded_url . '" rel="external" class="twitter-media" data-media="' . $media->media_url . '">' . $media->display_url . '</a>';
						$format = str_replace( $old_url, $replacement, $format );
					}
				}
			}

			return $format;
		}
			
			
		/*
		* Allows you to do what you want with display
		* returns $display
		*/		
		public function display_infos()
			{
			$data = $this->get_infos();
			$request = $this->request;
			$i = 1;
			
			if( !is_null($data) ) 
				{
			
				switch( $request )
					{

					case 'users/show':
						$display  = '<img src="'.$data->profile_image_url.'" width="36" height="36" alt="" />';
						$display .= '<ul>';
						$display .= '<li><span class="ttm-users-show label">'.__('name', $this->textdomain).'</span>'.' '.'<span class="ttm-users-show user-name">'.$data->name.'</span></li>';
						$display .= '<li><span class="ttm-users-show label">'.__('screen name', $this->textdomain).'</span>'.' '.'<span class="ttm-users-show screen-name">'.$data->screen_name.'</span></li>';
						$display .= '<li><span class="ttm-users-show label">'.__('tweets', $this->textdomain).'</span>'.' '.'<span class="ttm-users-show tweets-count">'.$data->statuses_count.'</span></li>';
						$display .= '<li><span class="ttm-users-show label">'.__('followers', $this->textdomain).'</span>'.' '.'<span class="ttm-users-show followers-count">'.$data->followers_count.'</span></li>';
						$display .= '<li><span class="ttm-users-show label">'.__('followings', $this->textdomain).'</span>'.' '.'<span class="ttm-users-show followings-count">'.$data->friends_count.'</span></li>';
						$display .= '<li><span class="ttm-users-show label">'.__('favorites', $this->textdomain).'</span>'.' '.'<span class="ttm-users-show favorites-count">'.$data->favourites_count.'</span></li>';
						$display .= '</ul>';
					break;
					
					case 'statuses/user_timeline':
						$display = '<ul>';
						$count = isset( $this->params['count'] ) ? $this->params['count'] : 1;
					
						while( $i < $count ) 
							{
							if ( isset( $data[$i - 1] ) ) 
								{
								$text = $this->jc_twitter_format( $data[$i - 1]->text, $data[$i - 1] );
								$id_str = $data[$i - 1]->id_str;
								$screen_name = $data[$i - 1]->user->screen_name;
								$name = $data[$i - 1]->user->name;
								$date = $data[$i - 1]->created_at;
								$date_format = 'j/m/y - '.get_option('time_format');
								$profile_image_url = $data[$i - 1]->user->profile_image_url;
								
								$display .= '<li class="ttm-user-timeline tweets">';
								$display .= '<img src="'.$profile_image_url.'" alt="@'.$screen_name.'"/>';
								$display .= '<span class="ttm-user-timeline name"><a href="https://twitter.com/'.$screen_name.'">'.$name.'</span></a>'."\t";
								$display .= '<span class="ttm-user-timeline screen-name"><a href="https://twitter.com/'.$screen_name.'">'.$screen_name.'</a></span>'."\t";
								$display .= '<span class="ttm-user-timeline date"><a href="https://twitter.com/'.$screen_name.'/statuses/'.$id_str.'">'.date( $date_format, strtotime($date) ).'</a>'."\n";	
								$display .= '<span class="ttm-user-timeline text">'.$text.'</span>'."\n";
								$display .= '<span class="ttm-user-timeline reply"><a href="https://twitter.com/intent/tweet?in_reply_to='.$id_str.'">'. __( 'Reply', $this->textdomain ) .'</a></span>'."\t";
								$display .= '<span class="ttm-user-timeline retweet"><a href="https://twitter.com/intent/retweet?tweet_id='.$id_str.'">'. __( 'Retweet', $this->textdomain ) .'</a> </span>'."\t";
								$display .= '<span class="ttm-user-timeline favorite"><a href="https://twitter.com/intent/favorite?tweet_id='.$id_str.'">'. __( 'Favorite', $this->textdomain ) .'</a></span>'."\t";
								$display .= '</li>';
								
								$i++;
								}
							}
						$display .= '</ul>';
					break;
					
					default:
						$display = __('This request does not exist or is not taken into account with the display_infos() method !', $this->textdomain);
					}
				
				}
			else 
				{
				
				$display = $data;
				
				}
				
			return apply_filters('the_twitter_display', $display);
		
			}
			
		
		/*
		* Delete cache
		* In case you need to delete transient
		*/
		protected function delete_cache()
			{
				delete_site_transient(substr(md5($this->request), 0, 10).'_ttm_transient');
			}
			
		}
	}