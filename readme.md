# TokenToMe 

Get access token and more from Twitter (in WordPress).

## Requirements 

consumer key and consumer secret : <a href="https://apps.twitter.com/app/new">Get yours !</a>


## Description 

### Get infos as object 

Class that allows you to grab data from Twitter in WordPress

    $init =  new TokenToMe\WP_Twitter_Oauth('CONSUMER_KEY', 'CONSUMER_SECRET', 'users/show', array('screen_name' => 'TweetPressFr') );
    $infos = $init->get_infos();
    var_dump($infos);
	
The fourth param should give you the ability to add additional param according to the Twitter's API documentation.

There's a cache, 15 minutes by default, you can customize it.

### Save your time

**GET statuses/user_timeline**

Here is an example with the `display_infos()` method and the request `GET statuses/user_timeline` :
	
	$init =  new TokenToMe\WP_Twitter_Oauth('CONSUMER_KEY', 'CONSUMER_SECRET', 'statuses/user_timeline', array('count' => 20, 'screen_name' => 'TweetPressFr') );
	$infos = $init->display_infos();

	echo $infos;
	
**GET users/lookup**

	$init =  new TokenToMe\WP_Twitter_Oauth('CONSUMER_KEY', 'CONSUMER_SECRET', 'users/lookup', array('screen_name' => 'TweetPressFr,twitter,support') );
	$infos = $init->display_infos();

	echo $infos;

### Fork this class

If you found something wrong, if you want to add stuffs, please fork the <a href="https://github.com/TweetPressFr/TokenToMe/tree/trunk">trunk version</a> not the master. Thanks.


## Changelog

# 1.5
* namespace, code standards

# 1.4
* fix mysql bugs with emoji => base64encode, thanks for the update raherian :)

# 1.3
* Fix notices when no settings or wrong settings are set
* Add dsiplay media option

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
