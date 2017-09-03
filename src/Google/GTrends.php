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
     * @param $kWord
     * @param int $category
     * @param string $time
     * @param string $property
     * @return array|bool
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

            $widgetsArray = Json\Json::decode(trim(substr($data, 4)), true)['widgets'];

            foreach ($widgetsArray as $widget) {

                if ($widget['title'] == 'Interest over time') {

                    $relatedPayload['hl'] = $this->options['hl'];
                    $relatedPayload['tz'] = $this->options['tz'];
                    $relatedPayload['req'] = Json\Json::encode($widget['request']);
                    $relatedPayload['token'] = $widget['token'];

                    $data = $this->_getData(self::INTEREST_OVER_TIME_URL, 'GET', $relatedPayload);
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
     * @return array|bool
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