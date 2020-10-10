<?php

namespace TokenToMe;
/*
Plugin Name: TokenToMe
Description: WordPress class to get access token from Twitter and more :)
Author: Julien Maury
Version: 2.0
Licence: GPLv3
*/

/**
 * This class is meant to ease your work with the new Twitter API v2 in WordPress
 * it has built-in methods to formats things and grab objects
 * there's also a display method to see examples or for your inspiration
 * 
 * It heavily relies on the new Twitter developer experience
 * Now you can get your bearer token directly that is why it's no longer generated inside the class
 * 
 * BUT BE EXTRA CAREFUL WHEN ASKING FOR A DEV ACCESS AND WHEN CREATING YOUR APP FOR CREDENTIALS
 * you need all permissions to access the expected data results !!!
 */
class WP_Twitter_Oauth
{

	protected $token;
	public $request;
	protected $query; // internal
	public $params = [];
	public $cache;
	public $display_media;
	public $is_debug;
	protected $searchedUsername; // internal

	/**
	 * it's heavy
	 * the purpose is to explore the API
	 * there will be enhancements and fixes in the next versions
	 */
	public const PARAMS_SEARCH_USER = ["from", "to", "@"];
	public const USER_FIELDS = ["created_at", "description", "entities, id", "location", "name", "profile_image_url", "protected", "public_metrics", "url", "username", "verified", "withheld",];
	public const TWEET_FIELDS = ["attachments", "author_id", "context_annotations", "conversation_id", "created_at", "entities", "geo", "id", "in_reply_to_user_id", "lang", "public_metrics", "possibly_sensitive", "referenced_tweets", "source", "text", "withheld",];
	public const EXPANSION_FIELDS = ["attachments.poll_ids", "attachments.media_keys", "author_id", "entities.mentions.username", "geo.place_id", "in_reply_to_user_id", "referenced_tweets.id", "referenced_tweets.id.author_id",];
	public const MEDIA_FIELDS = ["duration_ms", "height", "media_key", "preview_image_url", "type", "url", "width", "public_metrics",];
	public const POLL_FIELDS = ["duration_minutes", "end_datetime", "id", "options", "voting_status",];
	public const PLACE_FIELDS = ["contained_within", "country", "country_code", "full_name", "geo", "id", "name", "place_type",];

	public function __construct(
		string $BearerToken = "",
		string $Request = 'users/by',
		array $Params = [],
		int $Cache = 901,
		bool $Display_media = false,
		bool $isDebug = false
	) {

		$this->token 		   = $BearerToken;
		$this->request         = rtrim(ltrim($Request, "/"), "/");
		$this->params          = $Params;
		$this->cache           = $Cache;
		$this->display_media   = $Display_media;
		$this->is_debug        = $isDebug;

		if (
			!$BearerToken
			|| !$Request
		) {
			return __('The class is not set properly, you must provide a bearer token AND a request endpoint!', 'ttm');
		}

		if ($Cache < 901) { // 900 is for 15 min
			return __('The cache duration is very low, please set at least 900s.', 'ttm');
		}
	}

	protected function check_http_code($http_code)
	{

		switch ($http_code) {

			case '400':
			case '401':
			case '403':
			case '404':
			case '406':
				$error = '<div class="error">' . __('Your credentials might be unset or incorrect or username or request is wrong. In any case this error is not due to Twitter API.', 'ttm') . '</div>';
				break;

			case '429':
				$error = '<div class="error">' . __('Rate limits are exceed!', 'ttm') . '</div>';
				break;

			case '500':
			case '502':
			case '503':
				$error = '<div class="error">' . __('Twitter is overwhelmed or something bad happened with its API.', 'ttm') . '</div>';
				break;

			default:
				$error = '<div class="error">' . __('Something is wrong or missing. ', 'ttm') . '</div>';
		}

		return $error;
	}

	protected function get_obj()
	{
		$args = [
			'httpversion' => '1.1',
			'timeout'     => 120,
			'headers'     => [
				'Authorization' => "Bearer {$this->token}",
			],
		];

		$this->prepareQuery(); // here everything is set for the remote call
		$call  = wp_remote_get($this->query, $args);

		if (!is_wp_error($call) && 200 === wp_remote_retrieve_response_code($call)) {
			$obj = json_decode(wp_remote_retrieve_body($call));
		} else {
			$this->delete_cache();

			if ($this->is_debug) {
				throw new \Exception(sprintf("Eror, check data : %s", json_encode($call)));
			}

			$obj = $this->check_http_code(wp_remote_retrieve_response_code($call));
		}

		return apply_filters('the_twitter_object', $obj);
	}

	public function get_infos()
	{

		$set_cache = !empty($this->params) ? implode(',', $this->params) . $this->request : $this->request;

		if ($this->is_debug) {
			return $this->get_obj();
		}

		$cached = unserialize(base64_decode(get_site_transient(md5($set_cache)))); // tips with base64_decode props to @raherian

		if (false === (bool) $cached) {
			$cached = $this->get_obj();
			set_site_transient(md5($set_cache), base64_encode(serialize($cached)), $this->cache); //901 by default because Twitter says every 15 minutes in its doc
		}

		return $cached;
	}

	public function formatTweet($raw_text, $tweet = null)
	{

		// create xhtml safe text (mostly to be safe of ampersands)
		$format = htmlentities(html_entity_decode($raw_text, ENT_NOQUOTES, 'UTF-8'), ENT_NOQUOTES, 'UTF-8');

		// parse urls
		if (empty($tweet)) {
			// for regular strings, just create <a> tags for each url
			$pattern     = '/([A-Za-z]+:\/\/[A-Za-z0-9-_]+\.[A-Za-z0-9-_:%&\?\/.=]+)/i';
			$replacement = '<a href="${1}" rel="external">${1}</a>';
			$format      = preg_replace($pattern, $replacement, $format);
		} else {
			// for tweets, let's extract the urls from the entities object
			if (isset($tweet->entities->urls)) {
				foreach ($tweet->entities->urls as $url) {
					$old_url      = $url->url;
					$expanded_url = (empty($url->expanded_url)) ? $url->url : $url->expanded_url;
					$display_url  = (empty($url->display_url)) ? $url->url : $url->display_url;
					$replacement  = '<a href="' . $expanded_url . '" rel="external">' . $display_url . '</a>';
					$format       = str_replace($old_url, $replacement, $format);
				}
			}

			// let's extract the hashtags from the entities object
			if (isset($tweet->entities->hashtags)) {
				foreach ($tweet->entities->hashtags as $hashtags) {
					$hashtag     = '#' . $hashtags->tag;
					$replacement = '<a href="http://twitter.com/search?q=%23' . $hashtags->tag . '" rel="external">' . $hashtag . '</a>';
					$format      = str_ireplace($hashtag, $replacement, $format);
				}
			}

			// let's extract the usernames from the entities object
			if (isset($tweet->entities->mentions)) {
				foreach ($tweet->entities->mentions as $user_mentions) {
					$username    = '@' . $user_mentions->username;
					$replacement = '<a href="http://twitter.com/' . $user_mentions->username . '" rel="external" title="' . $user_mentions->username . '' . __('on Twitter', 'ttm') . '">' . $username . '</a>';
					$format      = str_ireplace($username, $replacement, $format);
				}
			}
		}

		return $format;
	}

	public function display_infos()
	{
		$data = $this->get_infos();

		if (empty($data->data) || !is_array($data->data)) {
			$this->delete_cache();

			if ($this->is_debug) {
				throw new \Exception(sprintf("Unexpected error, check data : %s", json_encode($data)));
			}
		}

		$infos = $data->data;
		$count = (int) apply_filters("the_twitter_count", $this->params["max_results"] ?? 7, $this->request, $this->params);
		$infos = array_slice($infos, 0, $count);

		switch ($this->request) {

			case 'users/by':

				$display = '';

				foreach ($infos as $d) :

					if (!isset($d->id)) {
						$display = __('Error display, there is no id for username which is weird. Check request and setup!', 'ttm');
					}

					if (!empty($d->profile_image_url) && !empty($d->username)) {
						$display .= '<img src="' . $d->profile_image_url . '" width="36" height="36" alt="@.' . $d->username . '" />';
					}

					$display .= '<ul class="ttm-container">';
					$display .= '<li><span class="ttm-users-show label">' . __('id:', 'ttm') . '</span>' . ' ' . '<span class="ttm-users-show id"><a href="https://twitter.com/' . $d->id . '">' . $d->id . '</a></span></li>';
					$display .= '<li><span class="ttm-users-show label">' . __('created at:', 'ttm') . '</span>' . ' ' . '<span class="ttm-users-show created-at">' . $d->created_at . '</span></li>';
					$display .= '<li><span class="ttm-users-show label">' . __('username:', 'ttm') . '</span>' . ' ' . '<span class="ttm-users-show username"><a href="https://twitter.com/' . $d->username . '">' . $d->username . '</a></span></li>';
					$display .= '<li><span class="ttm-users-show label">' . __('url: ', 'ttm') . '</span>' . ' ' . '<span class="ttm-users-show url"><a href="' . $d->url . '">' . $d->url . '</a></span></li>';
					$display .= '<li><span class="ttm-users-show label">' . __('description:', 'ttm') . '</span>' . ' ' . '<span class="ttm-users-show description">' . $d->description . '</span></li>';
					$display .= '<li><span class="ttm-users-show label">' . __('followers:', 'ttm') . '</span>' . ' ' . '<span class="ttm-users-show followers-count">' . $d->public_metrics->followers_count . '</span></li>';
					$display .= '<li><span class="ttm-users-show label">' . __('followings:', 'ttm') . '</span>' . ' ' . '<span class="ttm-users-show followings-count">' . $d->public_metrics->following_count . '</span></li>';
					$display .= '<li><span class="ttm-users-show label">' . __('tweets: ', 'ttm') . '</span>' . ' ' . '<span class="ttm-users-show tweet-count">' . $d->public_metrics->tweet_count . '</span></li>';
					$display .= '<li><span class="ttm-users-show label">' . __('verified:', 'ttm') . '</span>' . ' ' . '<span class="ttm-users-show verified">' . $this->formatBool($d->verified) . '</span></li>';
					$display .= '<li><span class="ttm-users-show label">' . __('protected:', 'ttm') . '</span>' . ' ' . '<span class="ttm-users-show protected">' . $this->formatBool($d->protected) . '</span></li>';
					$display .= '</ul>';

				endforeach;

				break;

			case 'tweets/search/recent':
				$display = '<ul class="ttm-container">';
				$class  = 'ttm-user-timeline';

				foreach ($infos as $d) {

					$text              = $this->formatTweet($d->text, $d);
					$id_str            = $d->id;
					$screen_name       = $this->searchedUsername;
					$date              = $d->created_at;
					$date_format       = 'j/m/y - ' . get_option('time_format');
					$pic_twitter       = '';

					if ($this->display_media && isset($d->includes->media)) {
						foreach ($d->includes->media as $media) {
							if (!isset($media->url)) {
								continue;
							}
							$pic_twitter = '<img width="100%" src="' . $media->url . '" alt="" />';
						}
					}

					$display .= '<li class="' . $class . ' tweets">';
					$display .= '<strong class="' . $class . ' name"><a href="https://twitter.com/' . $screen_name . '">@' . $screen_name . '</span></a></strong>' . "\t";
					$display .= '<span class="' . $class . ' date"><a href="https://twitter.com/' . $screen_name . '/status/' . $id_str . '">' . date($date_format, strtotime($date)) . '</a>' . "\n";
					$display .= '<span class="' . $class . ' text">' . $text . '</span>' . "\n";
					$display .= apply_filters('the_media_show', $pic_twitter);
					$display .= '<span class="' . $class . ' reply"><a class="Icon Icon--reply" href="https://twitter.com/intent/tweet?in_reply_to=' . $id_str . '">' . __('Reply', 'ttm') . '</a></span>' . "\t";
					$display .= '<span class="' . $class . ' retweet"><a class="Icon Icon--retweet" href="https://twitter.com/intent/retweet?tweet_id=' . $id_str . '">' . __('Retweet', 'ttm') . '</a> </span>' . "\t";
					$display .= '<span class="' . $class . ' favorite"><a class="Icon Icon--favorite" href="https://twitter.com/intent/favorite?tweet_id=' . $id_str . '">' . __('Favorite', 'ttm') . '</a></span>' . "\t";
					$display .= '</li>';
				}

				$display .= '</ul>';
				break;

			default:
				$this->delete_cache();
				$display = __('This request does not exist or is not supported by the display_infos() method !', 'ttm');
		}

		return apply_filters('the_twitter_display', $display);
	}

	protected function delete_cache()
	{
		$set_cache = !empty($this->params) ? implode(',', $this->params) . $this->request : $this->request;
		delete_site_transient(md5($set_cache)); // md5 cause the wp functions do not support a lot of chars which is great by the way.
	}

	protected function formatBool(bool $bool)
	{
		return $bool ? __("true", "ttm") : __("false", "ttm");
	}

	protected function formatParamSearch()
	{
		foreach (self::PARAMS_SEARCH_USER as $param) {
			if (!empty($this->params[$param])) {
				$this->params["query"] = sprintf("%s:%s", $param, $this->params[$param]);
				$this->searchedUsername = $this->params[$param];
				unset($this->params[$param]);
			}
		}
	}

	protected function prepareQuery()
	{

		$defaults = [
			"user.fields"  => self::USER_FIELDS,
		];

		if ($this->request === "users/by" && isset($this->params["max_results"])) {
			unset($this->params["max_results"]);
		}

		if ($this->request === "tweets/search/recent") {

			if (isset($this->params["usernames"])) { // prevent misusages
				unset($this->params["usernames"]);
			}

			if (isset($this->params["max_results"]) && $this->params["max_results"] < 10) { // prevent misusages
				$this->params["max_results"] = 10;
			}

			/**
			 * params overide it
			 */
			$defaults = wp_parse_args($defaults, [
				"tweet.fields" => self::TWEET_FIELDS,
				"expansions" => self::EXPANSION_FIELDS,
				"media.fields" => self::MEDIA_FIELDS,
				"poll.fields" => self::POLL_FIELDS,
				"place.fields" => self::PLACE_FIELDS,
			]);

			$this->formatParamSearch($this->params);

			if (!empty($this->params["usernames"])) {
				unset($this->params["usernames"]);
			}
		}

		$this->params = array_map(function ($line) {

			if (is_array($line)) {
				$line = implode(",", $line);
			}

			return preg_replace('/\s+/', '', $line); // a little bit heavy but prevent misusages
		}, wp_parse_args($defaults, $this->params));

		$q = "https://api.twitter.com/2/{$this->request}";
		$this->query = add_query_arg($this->params, $q);
	}
}
