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

Please see the [composer.json](composer.json) file.


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
$ composer require "x-fran/g-trends": "^2.0"
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

To see a demo output from all methods (okay, functions) please open in your favorite browser the index.php 


Usage
-----

### New instance of the class GTrends
    use Google\GTrends;
    
    # This options are by default if none provided
    $options = [
            'hl'  => 'en-US',
            'tz'  => -60, # last hour
            'geo' => 'IE',
        ];
    $gt = new GTrends($options);

### Interest Over Time

    print_r($gt->interestOverTime('Dublin'));

### Related Queries

    # You can add up to 5 keywords
    print_r( $gt->getRelatedSearchQueries(['Dublin', 'Madrid', 'Paris']));
    
### Realtime Search Trends
    # Categories for Realtime Search Trends are a single char str:
    print_r($gt->getRealTimeSearchTrends('all'));
    #
    # Categories
    # all : default
    # b : business
    # e : entertainment
    # m : health/medical
    # t : sci/tech
    # s : sports
    # h : top stories

### Daily Search Trends
    # print_r($gt->getDailySearchTrends());
    
### Trending Searches

    # p54 is Google's tricky and wired code for Ireland
    print_r($gt->trendingSearches('p54', date('Ymd')));
    #
    # National Region Codes:
    # IRELAND=p54
    # UNITED_STATES=p1
    # ARGENTINA=p30
    # AUSTRALIA=p8
    # AUSTRIA=p44
    # BELGIUM=p41
    # BRAZIL=p18
    # CANADA=p13
    # CHILE=p38
    # COLOMBIA=p32
    # CZECHIA=p43
    # DENMARK=p49
    # EGYPT=p29
    # FINLAND=p50
    # FRANCE=p16
    # GERMANY=p15
    # GREECE=p48
    # HONG_KONG=p10
    # HUNGARY=p45
    # INDIA=p3
    # INDONESIA=p19
    # ISRAEL=p6
    # ITALY=p27
    # JAPAN=p4
    # KENYA=p37
    # MALAYSIA=p34
    # MEXICO=p21
    # NETHERLANDS=p17
    # NEW_ZEALAND=p53
    # NIGERIA=p52
    # NORWAY=p51
    # PHILIPPINES=p25
    # POLAND=p31
    # PORTUGAL=p47
    # ROMANIA=p39
    # RUSSIA=p14
    # SAUDI_ARABIA=p36
    # SINGAPORE=p5
    # SOUTH_AFRICA=p40
    # SOUTH_KOREA=p23
    # SPAIN=p26
    # SWEDEN=p42
    # SWITZERLAND=p46
    # TAIWAN=p12
    # THAILAND=p33
    # TURKEY=p24
    # UKRAINE=p35
    # UNITED_KINGDOM=p9
    # VIETNAM=p28

### Interest by Subregion
    # You can add up to 5 keywords
    # Parameter $resolution (optional) for United States 'Subregion', 'Metro', 'City'
    # Parameter $resolution (optional) for the rest of the countries 'Subregion', 'City' only
    print_r($gt->relatedQueries(['Dublin'], 'City'));

### Suggestions Autocomplete

    print_r($gt->suggestionsAutocomplete('toys'));

    
## Common API parameters

$keyWordList (Array)

> Array of keywords (up to 5) to get data for

$category (Integer)

> Search by category
> Please view this [wiki page containing all available categories](https://github.com/pat310/google-trends-api/wiki/Google-Trends-Categories)

$tz (Integer)

> Timezone Offset
> For example US CST is ```360```

$time (String)

> Timezone Offset 

> **```'now 1-H'```** would get data from last hour (default)  
> **```'today 2-d'```** would get data from today to 2 days ago  
> **```'today 3-m'```** would get data from today to 3 months ago  
> **```'today 4-y'```** would get data from today to 4 years ago  


Caveats
-------

    - This is not an official or supported API
    - Rate Limit is not publicly known, let me know if you have a consistent estimate.


Credits
-------

* Some ideas pulled from General Mills's Google Trends API for Python
    - https://github.com/GeneralMills/pytrends
