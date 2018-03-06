<?php

namespace Google;

use Zend\Json;
use Zend\Http;
use Zend\Stdlib;

class GTrends
{
    const GENERAL_URL = 'https://trends.google.com/trends/api/explore';
    const RELATED_QUERIES_URL = 'https://trends.google.com/trends/api/widgetdata/relatedsearches';
    const INTEREST_OVER_TIME_URL = 'https://trends.google.com/trends/api/widgetdata/multiline';
    const TRENDING_SEARCHES_URL = 'https://trends.google.com/trends/hottrends/hotItems';
    const TOP_CHARTS_URL = 'https://trends.google.com/trends/topcharts/chart';
    const SUGGESTIONS_URL = 'https://trends.google.com/trends/api/autocomplete';
    const INTEREST_BY_SUBREGION_URL = 'https://trends.google.com/trends/api/widgetdata/comparedgeo';
    const LATEST_STORIES = 'https://www.google.com/trends/api/stories/latest';

    protected $options = [
        'hl' => 'en-US',
        'tz' => 360,
        'geo' => 'US',
    ];

    public function __construct(array $options=[])
    {
        if ($options) {

            $this->setOptions($options);
        }
    }

    /**
     * @param array $keyWordList
     * @param int $category
     * @param string $time
     * @param string $property
     * @param int $sleep
     * @return array|bool
     * @throws \Exception
     */
    public function relatedQueries(array $keyWordList, $category=0, $time='now 1-H', $property='', $sleep=5)
    {
        if (count($keyWordList) == 0 OR count($keyWordList) > 5) {

            throw new \Exception('Invalid number of items provided in keyWordList');
        }

        $comparisonItem = [];
        foreach ($keyWordList as $kWord) {

            $comparisonItem[] = ['keyword' => $kWord, 'geo' => $this->options['geo'], 'time' => $time];
        }

        $payload = [
            'hl' => $this->options['hl'],
            'tz' => $this->options['tz'],
            'req' => Json\Json::encode(['comparisonItem' => $comparisonItem, 'category' => $category, 'property' => $property]),
        ];
        $data = $this->_getData(self::GENERAL_URL, 'GET', $payload);
        if ($data) {

            $widgetsArray = Json\Json::decode(trim(substr($data, 5)), true)['widgets'];
            $results = [];
            foreach ($widgetsArray as $widget) {

                if ($widget['title'] == 'Related queries') {

                    $kWord = $widget['request']['restriction']['complexKeywordsRestriction']['keyword'][0]['value'];
                    $relatedPayload['hl'] = $this->options['hl'];
                    $relatedPayload['tz'] = $this->options['tz'];
                    $relatedPayload['req'] = Json\Json::encode($widget['request']);
                    $relatedPayload['token'] = $widget['token'];
                    $data = $this->_getData(self::RELATED_QUERIES_URL, 'GET', $relatedPayload);
                    if ($data) {

                        $queriesArray = Json\Json::decode(trim(substr($data, 5)), true);
                        $results[$kWord] = $queriesArray;

                        if (count($keyWordList)>1) {

                            sleep($sleep);
                        }
                    } else {

                        return false;
                    }
                }
            }

            return $results;
        }

        return false;
    }

	/**
	 * @param        $kWord
	 * @param int    $category
	 * @param string $time
	 * @param string $property
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
    public function interestOverTime($kWord, $category=0, $time='now 1-H', $property='')
    {
        $comparisonItem[] = ['keyword' => $kWord, 'geo' => $this->options['geo'], 'time' => $time];
        $payload = [
            'hl' => $this->options['hl'],
            'tz' => $this->options['tz'],
            'req' => Json\Json::encode(['comparisonItem' => $comparisonItem, 'category' => $category, 'property' => $property]),
        ];

        $data = $this->_getData(self::GENERAL_URL, 'GET', $payload);
        if ($data) {

            $widgetsArray = Json\Json::decode(trim(substr($data, 4)), Json\Json::TYPE_ARRAY)['widgets'];

            foreach ($widgetsArray as $widget) {

                if ($widget['title'] == 'Interest over time') {

                    $interestOverTimePayload['hl'] = $this->options['hl'];
                    $interestOverTimePayload['tz'] = $this->options['tz'];
                    $interestOverTimePayload['req'] = Json\Json::encode($widget['request']);
                    $interestOverTimePayload['token'] = $widget['token'];

                    $data = $this->_getData(self::INTEREST_OVER_TIME_URL, 'GET', $interestOverTimePayload);
                    if ($data) {

                        return Json\Json::decode(trim(substr($data, 5)), true)['default']['timelineData'];
                    } else {

                        return false;
                    }
                }
            }
        }

        return false;
    }

	/**
	 * @param $country
	 * @param $date
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
    public function trendingSearches($country, $date)
    {
        $params = [
            'ajax' => '1',
            'pn' => $country,
            'htd' => '',
            'htv' => 'l',
            'std' => $date,
        ];
        $data =  $this->_getData(self::TRENDING_SEARCHES_URL, 'POST', $params);
        if ($data) {

            return Json\Json::decode($data, true);
        } else {

            return false;
        }
    }

	/**
	 * @param        $date
	 * @param        $cid
	 * @param string $geo
	 * @param string $cat
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
    public function topCharts($date, $cid, $geo='US', $cat='')
    {
        $chartsPayload = [
            'ajax' => 1,
            'lp' => 1,
            'geo' => $geo,
            'date' => $date,
            'cat' => $cat,
            'cid' => $cid,
        ];
        $data = $this->_getData(self::TOP_CHARTS_URL, 'GET', $chartsPayload);
        if ($data) {

            return Json\Json::decode(trim($data), true);
        }
        return false;
    }

	/**
	 * @param $kWord
	 *
	 * @return array|bool
	 * @throws \Exception
	 */
    public function suggestionsAutocomplete($kWord)
    {
        $uri = self::SUGGESTIONS_URL . "/'$kWord'";
        $param = ['hl' => $this->options['hl']];
        $data = $this->_getData($uri, 'GET', $param);
        if ($data) {

            return Json\Json::decode(trim(substr($data, 5)), true);
        }
        return false;
    }

    /**
     * @param array $keyWordList
     * @param string $resolution
     * @param int $category
     * @param string $time
     * @param string $property
     * @param int $sleep
     * @return array|bool
     * @throws \Exception
     */
    public function interestBySubregion(array $keyWordList, $resolution='SUBREGION', $category=0, $time='now 1-H', $property='', $sleep=5)
    {
        if (count($keyWordList) == 0 OR count($keyWordList) > 5) {

            throw new \Exception('Invalid number of items provided in keyWordList');
        }

        $comparisonItem = [];
        foreach ($keyWordList as $kWord) {
            $comparisonItem[] = ['keyword' => $kWord, 'geo' => $this->options['geo'], 'time' => $time];
        }

        $payload = [
            'hl' => $this->options['hl'],
            'tz' => $this->options['tz'],
            'req' => Json\Json::encode(['comparisonItem' => $comparisonItem, 'category' => $category, 'property' => $property]),
        ];

        $data = $this->_getData(self::GENERAL_URL, 'GET', $payload);
        if ($data) {

            $widgetsArray = Json\Json::decode(trim(substr($data, 5)), true)['widgets'];

            $results = [];
            foreach ($widgetsArray as $widget) {

                if ($widget['title'] == 'Interest by subregion') {

                    $kWord = $widget['bullet'];
                    if (!$this->options['geo']) {

                        $widget['request']['resolution'] = ucfirst(strtolower($resolution));
                    }
                    $interestBySubregionPayload['hl'] = $this->options['hl'];
                    $interestBySubregionPayload['tz'] = $this->options['tz'];
                    $interestBySubregionPayload['req'] = Json\Json::encode($widget['request']);
                    $interestBySubregionPayload['token'] = $widget['token'];

                    $data = $this->_getData(self::INTEREST_BY_SUBREGION_URL, 'GET', $interestBySubregionPayload);
                    if ($data) {

                        $queriesArray = Json\Json::decode(trim(substr($data, 5)), true);
                        $results[$kWord] = $queriesArray;

                        if (count($keyWordList)>1) {

                            sleep($sleep);
                        }
                    } else {

                        return false;
                    }
                }
            }

            return $results;
        }

        return false;
    }

	/**
	 * @param string $country
	 * @param string $cat
	 * @param string $geo
	 * @param int    $tz
	 *
	 * @return bool|mixed
	 * @throws \Exception
	 */
	public function latestStories($country='en-US', $cat='all', $geo='IE', $tz=-60)
    {
  		$params = [
  			'hl' => $country,
  			'cat' => $cat,
  			'fi' => 15,
  			'fs' => 15,
  			'geo' => $geo,
  			'ri' => 300,
  			'rs' => 15,
  			'tz' => $tz,
  		];
      $data = $this->_getData(self::LATEST_STORIES, 'GET', $params);
      if ($data) {
			     return Json\Json::decode(trim(substr($data, 4)), true);
      }
      return false;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return GTrends
     * @throws \Exception
     */
    public function setOptions(array $options): GTrends
    {
        if (count($options) != 3) {

            throw new \Exception("Invalid number of options provided");
        } elseif (!key_exists('hl', $options) OR !key_exists('tz', $options) OR !key_exists('geo', $options)) {

            throw new \Exception("Invalid keys provided");
        } elseif (!is_string($options['hl']) OR !is_int($options['tz']) OR !is_string($options['geo'])) {

            throw new \Exception("Invalid type values provided");
        } else {

            $this->options = $options;
            return $this;
        }
    }

    /**
     * @param $uri
     * @param $method
     * @param array $params
     * @return bool|string
     * @throws \Exception
     */
    private function _getData($uri, $method, array $params=[])
    {
        if ($method != 'GET' AND $method != 'POST') {

            throw new \Exception(__METHOD__ . " $method Method not allowed");
        }

        $client = new Http\Client();
        $client->setOptions([
            'adapter' => Http\Client\Adapter\Curl::class,
            'maxredirects' => 10,
            'timeout' => 100]);
        $client->setUri($uri);
        $client->setMethod(strtoupper($method));

        if (strtoupper($method) == 'POST') {

            $client->getRequest()->getHeaders()->addHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);
        }

        if ($params) {

            if (strtoupper($method) == 'GET') {

                $client->setParameterGet($params);
            }

            if (strtoupper($method) == 'POST') {

                $client->getRequest()->setQuery(new Stdlib\Parameters($params));
            }
        }

        $client->send();
        $statusCode = $client->getResponse()->getStatusCode();

        if ($statusCode == 200) {

            $headers = $client->getResponse()->getHeaders()->toArray();
            $getData = false;
            foreach ($headers as $header => $value) {

                if ($header == 'Content-Type') {

                    if (
                        stripos($value, 'application/json') !== false OR
                        stripos($value, 'application/javascript') !== false OR
                        stripos($value, 'text/javascript') !== false
                    ) {

                        $getData = true;

                        break;
                    }
                }
            }

            if ($getData) {

                if ($body = $client->getResponse()->getBody()) {

                    return $body;
                }
            }
        }

        return false;
    }
}
