# g-trends


Google Trends API for PHP
=========================


Introduction
------------

This is an unofficial Google Trends API for PHP.

Please notice that the good functionality of this API depends on Google's willing to keep the backward compatibility and/or the parameters, naming and/or required values.   
If this happens, feel free to contribute or open an issue.


Requirements
------------

PHP 8.3 or higher. Please see the [composer.json](composer.json) file.

Status (September 2026)
-----------------------

Google retired the `dailytrends` and `realtimetrends` endpoints in 2025. Since 5.0 this library
reads Google's "Trending Now" page instead, which returns richer data (search volume, growth,
related queries, topics). The explore-based methods (interest over time, related queries, related
topics, compared geo) and the pickers (suggestions, categories, geo) still work.

5.0 is a rewrite with a new API — see Usage below. It requires PHP 8.3+.


Installation
------------

### Via Composer (require)

If you have composer installed globally
```bash
$ composer require x-fran/g-trends
```

If you use composer.phar local
```bash
# Get your own copy of composer.phar
$ curl -s https://getcomposer.org/installer | php -- --filename=composer
$ composer require "x-fran/g-trends": "^5.0"
```


### Via Composer (create-project)

You can use the `create-project` command from [Composer](http://getcomposer.org/)
to create the project in one go (you need to install [composer](https://getcomposer.org/doc/00-intro.md#downloading-the-composer-executable)):

```bash
$ curl -s https://getcomposer.org/installer | php -- --filename=composer
$ composer --no-dev create-project x-fran/g-trends path/to/install
```

### Via Git (clone)

First, clone the repository:

```bash
$ git clone https://github.com/x-fran/g-trends.git # optionally, specify the directory in which to clone
$ cd path/to/install
$ curl -s https://getcomposer.org/installer | php -- --filename=composer
```

At this point, you need to use [Composer](https://getcomposer.org/) to install
dependencies. Assuming you already have Composer:

```bash
$ composer --no-dev install
```

Demo
----

Open [index.php](index.php) in a browser (or `php -S localhost:8000`) to see the output of every method.


Usage
-----

### Create a client

```php
use XFran\GTrends\GTrends;

// All arguments are optional; these are the defaults
$gt = new GTrends(
    hl: 'en-US',    // interface language
    tz: 0,          // timezone offset in minutes, e.g. 360 for US CST
    geo: 'US',      // country code, '' for worldwide
    time: 'all',    // explore range: 'now 1-H', 'now 1-d', 'today 3-m', 'today 5-y', 'all'
    category: 0,    // explore category id, see getCategories()
);

// Route through a proxy to avoid Google's captcha / rate limits
$gt = new GTrends(geo: 'IE', proxy: [
    'proxy_host' => 'your_proxy_host',
    'proxy_port' => 8000,
    'proxy_user' => 'your_proxy_user',
    'proxy_pass' => 'your_proxy_pass',
]);
```

Every method throws `XFran\GTrends\GTrendsException` when Google answers with a non-200 status
(HTTP 429 = rate limited) or an unexpected payload, and `InvalidArgumentException` for bad arguments.

### Trending Now

Google retired the `dailytrends` and `realtimetrends` endpoints in 2025. `getTrendingNow()` reads
the list behind [trends.google.com/trending](https://trends.google.com/trending) instead.

```php
// Everything trending in $geo over the last 4, 24, 48 or 168 hours
$gt->getTrendingNow(24);

// Restrict to a topic (ids and names in GTrends::TRENDING_TOPICS)
$gt->getTrendingNow(4, 17); // Sports, last 4 hours

// Each entry:
[
    'keyword'             => 'alabama vs kentucky',
    'geo'                 => 'US',
    'startedAt'           => 1789216200,   // unix timestamp
    'searchVolume'        => 500000,
    'volumeGrowthPercent' => 1000,
    'relatedQueries'      => ['alabama football', 'kentucky football', ...],
    'topics'              => [17],
    'topicNames'          => ['Sports'],
    'newsArticleIds'      => [4814762364, ...],
]

// Google's public RSS feed for the same page: fewer fields, but it carries news headlines/urls
// and it is the only trending feed Google exposes on purpose
$gt->getTrendingNowRss();
```

### Explore (interest over time, related queries, related topics, compared geo)

Up to 5 keywords per call.

```php
$gt->getInterestOverTime('Dublin');                 // ['timelineData' => [...], 'averages' => [...]]
$gt->getInterestOverTime(['Dublin', 'Cork']);

$gt->getRelatedQueries(['Dublin', 'Cork']);         // ['Dublin' => ['rankedList' => ...], 'Cork' => ...]
$gt->getRelatedTopics('Dublin');                    // single keyword only (Google's limitation)

$gt->getComparedGeo('Dublin', 'REGION');            // resolution: COUNTRY (worldwide geo only), REGION, CITY

// Or fetch several widgets with a single explore round-trip
$gt->explore(['Dublin', 'Cork'], [GTrends::TIMESERIES, GTrends::RELATED_QUERIES]);
// => ['TIMESERIES' => [...], 'RELATED_QUERIES' => ['Dublin' => [...], 'Cork' => [...]]]
```

The widget payloads are returned exactly as Google sends them.

### Pickers

```php
$gt->getSuggestions('Milwaukee');   // [['mid' => '/m/0c1xr', 'title' => 'Milwaukee', 'type' => 'City in Wisconsin'], ...]
$gt->getCategories();               // category tree for the `category` constructor argument
$gt->getGeo();                      // geo tree for the `geo` constructor argument
```


Development
-----------

```bash
composer install
composer test        # PHPUnit — hits the live Google API, so it needs network access
composer check       # php-cs-fixer, PHPStan (level 8), Psalm, PHPUnit
```


Caveats
-------

    - This is not an official or supported API; every endpoint is undocumented and can change without notice
    - Rate limits are not publicly known. Google throttles by IP: sustained explore use returns HTTP 429 and the block can persist for a while. Use the `proxy` option if you hit it


Credits
-------

* Some ideas pulled from General Mills's Google Trends API for Python
    - https://github.com/GeneralMills/pytrends
