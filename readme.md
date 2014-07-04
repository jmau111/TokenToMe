# TokenToMe 

Get access token and more from Twitter (in WordPress)

## Requirements 

consumer key and consumer secret : <a href="https://apps.twitter.com/app/new">Get yours !</a>


## Description 

### Get infos as object 

Class that allows you to grab data from Twitter in WordPress

    $init =  new TokenToMe('CONSUMER_KEY', 'CONSUMER_SECRET', 'users/show', array('screen_name' => 'TweetPressFr') );
    $infos = $init->get_infos();
	var_dump($infos);
	
The fourth param should give you the ability to add additional param according to the Twitter's API documentation.

There's a cache (the very last param for the class), 15 minutes by default.

### Save your time

**GET statuses/user_timeline**

Here is an example with the `display_infos()` method and the request `GET statuses/user_timeline` :
	
	$init =  new TokenToMe('CONSUMER_KEY', 'CONSUMER_SECRET', 'statuses/user_timeline', array('count' => 20, 'screen_name' => 'TweetPressFr') );
	$infos = $init->display_infos();

	echo $infos;
	
**GET users/lookup**

	$init =  new TokenToMe('CONSUMER_KEY', 'CONSUMER_SECRET', 'users/lookup', array('screen_name' => 'TweetPressFr,twitter,support') );
	$infos = $init->display_infos();

	echo $infos;


## Changelog 

# 1.2
* Add checking method
* Add display method
* Add display for users/show, users/lookup, statuses/user_timeline, search/tweets
* Access_token as option
* Delete screen name param (could be blocking for other requests)

# 1.1
* Fix some bugs in case class is not set properly
* add method to Twitter's object according to any request

# 1.0
* First commit
