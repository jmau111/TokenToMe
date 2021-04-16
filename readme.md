# TokenToMe

Get tweets and more from Twitter API V2 (in WordPress).

## Requirements

-   Go get your token here : <https://developer.twitter.com/content/developer-twitter/en/apply-for-access>
-   Please remember to follow [style requirements](https://developer.twitter.com/en/developer-terms/display-requirements) by Twitter when using this library

## Description

This cass allows you to grab data from Twitter in WordPress.

### You need a bearer token to use this tool

Go get your token here : <https://developer.twitter.com/content/developer-twitter/en/apply-for-access>.

Read the documentation carefully, you cannot get some data if your token is not correctly configured. Make sure you provide all details to the Twitter team. Otherwise you app could be either rejected or useless.

### New version of the API and breaking changes

All details here <https://developer.twitter.com/en/docs/getting-started>. 
This API change is major, so is the new version of `WP_Twitter_Oauth`.

It's quite experimental, so please do not hesitate to report any bugs or suggestion. Issues are meant to that.

### Misusage protection

There are some protections to prevent the most common misusages of the Twitter API but it's not bullet proof, it's just a tool...

### Get infos as object

```php
$init =  new \TokenToMe\WP_Twitter_Oauth(
    'YOUR_BEARER_TOKEN', 
    'tweets/recent/search',
    [
        'from' => 'YOUR_USERNAME',
        'max_results' => 10
    ],
    901,
    true, // display media attached
    true // debug mode, no cache
);

$init2 =  new \TokenToMe\WP_Twitter_Oauth(
    'YOUR_BEARER_TOKEN', 
    'users/by',
    [
        'usernames' => 'rihanna, twitterapi, katiemelua',
    ],
    901,
    true, // display media attached
    true // debug mode, no cache
);

echo '<pre>';
print_r( $init->get_infos() );
print_r( $init2->get_infos() );
echo '</pre>';
```

The third param should give you the ability to add additional param according to the Twitter's API documentation.

There's a cache, 15 minutes by default, you can customize it.

### Save your time

**GET /v2/users/by**

Here is an example with the `display_infos()` method and the request `GET /v2/users/by` :

```php
$init =  new \TokenToMe\WP_Twitter_Oauth(
    'YOUR_BEARER_TOKEN', 
    'tweets/search/recent',
    [
        'from' => 'YOUR_USERNAME',
        'max_results' => 10
    ],
    3600, // set cache for 1 hour
    true // display media attached
);

echo $init->display_infos();
```

**GET /v2/tweets/search/recent**

```php
$init2 =  new \TokenToMe\WP_Twitter_Oauth(
    'YOUR_BEARER_TOKEN', 
    'users/by',
    [
        'usernames' => 'rihanna, twitterapi, katiemelua',
    ],
    3600, // set cache for 1 hour
    true // display media attached
);

echo $init2->display_infos();
```

![](/assets/screen_users_by.jpg?raw=true)

### Forking this class

If you found something wrong, if you want to add stuffs, please contribute with a pull request. Thanks.

## Changelog

# 2.0.2

- Fix ugly indentation, use space now

# 2.0

- Twitter API v2

# 1.1

- Twitter API v1.1
