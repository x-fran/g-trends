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
    const TRENDING_SEARCHES_REALTIME_URL = 'https://trends.google.com/trends/api/realtimetrends';
    const TOP_CHARTS_URL = 'https://trends.google.com/trends/topcharts/chart';
    const TOP_CHARTS_CATEGORY_URL = 'https://trends.google.com/trends/topcharts/category';
    const SUGGESTIONS_URL = 'https://trends.google.com/trends/api/autocomplete';
    const INTEREST_BY_SUBREGION_URL = 'https://trends.google.com/trends/api/widgetdata/comparedgeo';
    const CATEGORIES_URL = 'https://trends.google.com/trends/api/explore/pickers/category';

    protected $options = [
        'hl' => 'en-US',
        'tz' => 360,
        'geo' => 'US',
    ];

    /**
     * GTrends constructor.
     * @param array $options
     * @throws \Exception
     */
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
    private function _relatedQueries($keyWordList, $category=0, $time='today 12-m', $property='', $sleep=0.5)
    {
        if (null !== $keyWordList && ! is_array($keyWordList)) {
            throw new \InvalidArgumentException('Keyword list must be null or an array');
        }

        $timeInfo = explode('-', $time);
        $timeInfo[0] = strtolower($timeInfo[0]);
        $timeInfo[1] = strtolower($timeInfo[1]);
        $time = implode('-', $timeInfo);

        if (null === $keyWordList) {
            $comparisonItem[] = ['geo' => $this->options['geo'], 'time' => $time];
        } else {
            if (count($keyWordList) == 0 OR count($keyWordList) > 5) {

                throw new \Exception('Invalid number of items provided in keyWordList');
            }

            $comparisonItem = [];
            foreach ($keyWordList as $kWord) {

                $comparisonItem[] = ['keyword' => $kWord, 'geo' => $this->options['geo'], 'time' => $time];
            }
        }

        $payload = [
            'hl' => $this->options['hl'],
            'tz' => $this->options['tz'],
            'req' => Json\Json::encode(['comparisonItem' => $comparisonItem, 'category' => $category, 'property' => $property]),
        ];

        $data = $this->_getData(self::GENERAL_URL, 'GET', $payload);

        if ($data) {

            $widgetsArray = Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY)['widgets'];
            $results = [];
            foreach ($widgetsArray as $widget) {

                if ($widget['id'] === 'RELATED_QUERIES') {

                    $kWord = $widget['request']['restriction']['complexKeywordsRestriction']['keyword'][0]['value'] ?? null;
                    $relatedPayload['hl'] = $this->options['hl'];
                    $relatedPayload['tz'] = $this->options['tz'];
                    $relatedPayload['req'] = Json\Json::encode($widget['request']);
                    $relatedPayload['token'] = $widget['token'];
                    $data = $this->_getData(self::RELATED_QUERIES_URL, 'GET', $relatedPayload);
                    if ($data) {

                        $queriesArray = Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY);

                        if (null === $kWord) {
                            $results = $queriesArray;
                        } else {
                            $results[$kWord] = $queriesArray;
                        }

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

    public function relatedQueries(array $keyWordList, $category=0, $time='today 12-m', $property='', $sleep=0.5)
    {
        return $this->_relatedQueries($keyWordList, $category, $time, $property, $sleep);
    }

    public function searchQueries($category=0, $time='today 12-m', $property='', $sleep=0.5)
    {
        return $this->_relatedQueries(null, $category, $time, $property, $sleep);
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
    private function _relatedTopics($kWord, $category=0, $time='today 12-m', $property='')
    {
        $timeInfo = explode('-', $time);
        $timeInfo[0] = strtolower($timeInfo[0]);
        $timeInfo[1] = strtolower($timeInfo[1]);
        $time = implode('-', $timeInfo);

        if (null === $kWord) {
            $comparisonItem[] = ['geo' => $this->options['geo'], 'time' => $time];
        } else {
            $comparisonItem[] = ['keyword' => $kWord, 'geo' => $this->options['geo'], 'time' => $time];
        }

        $payload = [
            'hl' => $this->options['hl'],
            'tz' => $this->options['tz'],
            'req' => Json\Json::encode(['comparisonItem' => $comparisonItem, 'category' => $category, 'property' => $property]),
        ];

        $data = $this->_getData(self::GENERAL_URL, 'GET', $payload);
        if ($data) {

            $widgetsArray = Json\Json::decode(trim(substr($data, 4)), Json\Json::TYPE_ARRAY)['widgets'];

            foreach ($widgetsArray as $widget) {
                if ($widget['id'] === 'RELATED_TOPICS') {
                    $relatedPayload['hl'] = $this->options['hl'];
                    $relatedPayload['tz'] = $this->options['tz'];
                    $relatedPayload['req'] = Json\Json::encode($widget['request']);
                    $relatedPayload['token'] = $widget['token'];

                    $data = $this->_getData(self::RELATED_QUERIES_URL, 'GET', $relatedPayload);
                    if ($data) {

                        return Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY);
                    } else {

                        return false;
                    }
                }
            }
        }

        return false;
    }

    public function relatedTopics($kWord, $category=0, $time='today 12-m', $property='')
    {
        if (strlen(trim($kWord)) === 0) {
            throw new \InvalidArgumentException('Keyword must not be empty');
        }

        return $this->_relatedTopics($kWord, $category, $time, $property);
    }

    public function searchTopics($category=0, $time='today 12-m', $property='')
    {
        return $this->_relatedTopics(null, $category, $time, $property);
    }

    public function explore($keyWordList, $category=0, $time='today 12-m', $property='', array $widgetIds = ['*'], $sleep=0.5)
    {
        if (null !== $keyWordList && ! is_array($keyWordList)) {
            $keyWordList = [$keyWordList];
        }

        $timeInfo = explode('-', $time);
        $timeInfo[0] = strtolower($timeInfo[0]);
        $timeInfo[1] = strtolower($timeInfo[1]);
        $time = implode('-', $timeInfo);

        if (null === $keyWordList) {
            $comparisonItem[] = ['geo' => $this->options['geo'], 'time' => $time];
        } else {
            if (count($keyWordList) == 0 OR count($keyWordList) > 5) {

                throw new \Exception('Invalid number of items provided in keyWordList');
            }

            $comparisonItem = [];
            foreach ($keyWordList as $kWord) {

                $comparisonItem[] = ['keyword' => $kWord, 'geo' => $this->options['geo'], 'time' => $time];
            }
        }

        $payload = [
            'hl' => $this->options['hl'],
            'tz' => $this->options['tz'],
            'req' => Json\Json::encode(['comparisonItem' => $comparisonItem, 'category' => $category, 'property' => $property]),
        ];

        $data = $this->_getData(self::GENERAL_URL, 'GET', $payload);

        if (! $data) {

            return false;
        }

        $widgetsArray = Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY)['widgets'];
        $results = [];
        foreach ($widgetsArray as $widget) {

            $widgetEnabled = false !== array_search('*', $widgetIds) || in_array($widget['id'], $widgetIds, true);

            if (! $widgetEnabled) {

                continue;
            }

            if ($widget['id'] === 'TIMESERIES') {
                $interestOverTimePayload['hl'] = $this->options['hl'];
                $interestOverTimePayload['tz'] = $this->options['tz'];
                $interestOverTimePayload['req'] = Json\Json::encode($widget['request']);
                $interestOverTimePayload['token'] = $widget['token'];

                $data = $this->_getData(self::INTEREST_OVER_TIME_URL, 'GET', $interestOverTimePayload);
                if ($data) {

                    $results['TIMESERIES'] = Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY)['default']['timelineData'];
                } else {

                    $results['TIMESERIES'] = false;
                }
            }

            if (strpos($widget['id'], 'GEO_MAP') === 0) {

                $interestBySubregionPayload['hl'] = $this->options['hl'];
                $interestBySubregionPayload['tz'] = $this->options['tz'];
                $interestBySubregionPayload['req'] = Json\Json::encode($widget['request']);
                $interestBySubregionPayload['token'] = $widget['token'];

                $data = $this->_getData(self::INTEREST_BY_SUBREGION_URL, 'GET', $interestBySubregionPayload);
                if ($data) {

                    $queriesArray = Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY);

                    if (isset($widget['bullets'])) {
                        $queriesArray['bullets'] = $widget['bullets'];
                    }

                    $results['GEO_MAP'][$widget['bullet'] ?? ''] = $queriesArray;
                } else {

                    $results['GEO_MAP'] = false;
                }
            }

            if ($widget['id'] === 'RELATED_QUERIES') {

                $kWord = $widget['request']['restriction']['complexKeywordsRestriction']['keyword'][0]['value'] ?? null;
                $relatedPayload['hl'] = $this->options['hl'];
                $relatedPayload['tz'] = $this->options['tz'];
                $relatedPayload['req'] = Json\Json::encode($widget['request']);
                $relatedPayload['token'] = $widget['token'];
                $data = $this->_getData(self::RELATED_QUERIES_URL, 'GET', $relatedPayload);
                if ($data) {

                    $queriesArray = Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY);

                    if (null === $kWord || count($keyWordList) === 1) {

                        $results['RELATED_QUERIES'] = $queriesArray;
                    } else {

                        $results['RELATED_QUERIES'][$kWord] = $queriesArray;
                    }
                } else {

                    $results['RELATED_QUERIES'] = false;
                }
            }

            if ($widget['id'] === 'RELATED_TOPICS') {
                $relatedPayload['hl'] = $this->options['hl'];
                $relatedPayload['tz'] = $this->options['tz'];
                $relatedPayload['req'] = Json\Json::encode($widget['request']);
                $relatedPayload['token'] = $widget['token'];

                $data = $this->_getData(self::RELATED_QUERIES_URL, 'GET', $relatedPayload);
                if ($data) {

                    $results['RELATED_TOPICS'] = Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY);
                } else {

                    $results['RELATED_TOPICS'] = false;
                }
            }

            usleep($sleep * 1000 * 1000);
        }

        return $results;
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
    public function interestOverTime($kWord, $category=0, $time='now 1-h', $property='')
    {
        $timeInfo = explode('-', $time);
        $timeInfo[0] = strtolower($timeInfo[0]);
        $timeUnit = array_pop($timeInfo);
        $timeInfo[] = strtoupper($timeUnit);
        $time = implode('-', $timeInfo);

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

                        return Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY)['default']['timelineData'];
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

            return Json\Json::decode($data, Json\Json::TYPE_ARRAY);
        } else {

            return false;
        }
    }

    public function trendingSearchesRealtime($cat='all', $fi=0, $fs=0, $ri=300, $rs=20, $sort=0)
    {
        $params =[
            'hl' => $this->options['hl'],
            'tz' => $this->options['tz'],
            'geo' => $this->options['geo'],
            'cat' => $cat,
            'fi' => $fi,
            'fs' => $fs,
            'ri' => $ri,
            'rs' => $rs,
            'sort' => $sort,
        ];

        $uri = self::TRENDING_SEARCHES_REALTIME_URL;
        $data = $this->_getData($uri, 'GET', $params);

        if ($data) {
            return Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY);
        }

        return false;
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

            return Json\Json::decode(trim($data), Json\Json::TYPE_ARRAY);
        }
        return false;
    }

    /**
     * @param $date
     *
     * @return array|bool
     * @throws \Exception
     */
    public function topChartsCategories($date, $geo=null)
    {
        $params = [
            'ajax' => '1',
            'date' => $date,
            'geo' => $geo ?? $this->options['geo'],
            'cid' => '',
        ];
        $data =  $this->_getData(self::TOP_CHARTS_CATEGORY_URL, 'POST', $params);
        if ($data) {

            return Json\Json::decode($data, Json\Json::TYPE_ARRAY);
        } else {

            return false;
        }
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

            return Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY);
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
    private function _interestBySubregion(array $keyWordList, $resolution, $category=0, $time='now 1-h', $property='', $sleep=0.5, $subregion=null)
    {
        if (count($keyWordList) == 0 OR count($keyWordList) > 5) {

            throw new \Exception('Invalid number of items provided in keyWordList');
        }

        $geo = $this->options['geo'] . (null === $subregion ? '' : '-'.strtoupper($subregion));

        $timeInfo = explode('-', $time);
        $timeInfo[0] = strtolower($timeInfo[0]);
        $timeUnit = array_pop($timeInfo);
        $timeInfo[] = strtoupper($timeUnit);
        $time = implode('-', $timeInfo);

        $comparisonItem = [];
        foreach ($keyWordList as $kWord) {
            $comparisonItem[] = ['keyword' => $kWord, 'geo' => $geo, 'time' => $time];
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

                if (strpos($widget['id'], 'GEO_MAP') === 0) {

                    $widget['request']['resolution'] = strtoupper($resolution);

                    $interestBySubregionPayload['hl'] = $this->options['hl'];
                    $interestBySubregionPayload['tz'] = $this->options['tz'];
                    $interestBySubregionPayload['req'] = Json\Json::encode($widget['request']);
                    $interestBySubregionPayload['token'] = $widget['token'];

                    $data = $this->_getData(self::INTEREST_BY_SUBREGION_URL, 'GET', $interestBySubregionPayload);
                    if ($data) {

                        $queriesArray = Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY);

                        if (isset($widget['bullets'])) {
                            $queriesArray['bullets'] = $widget['bullets'];
                        }

                        $results[$widget['bullet'] ?? ''] = $queriesArray;

                        if (count($keyWordList)>1) {

                            usleep($sleep * 1000 * 1000);
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

    public function interestBySubregion(array $keyWordList, $resolution='SUBREGION', $category=0, $time='now 1-h', $property='', $sleep=0.5)
    {
        $resolution = strcasecmp('SUBREGION', $resolution) === 0 ? 'REGION' : $resolution;

        return $this->_interestBySubregion($keyWordList, $resolution, $category, $time, $property, $sleep);
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
    public function interestByRegion(array $keyWordList, $category=0, $time='now 1-h', $property='', $sleep=0.5)
    {
        return $this->_interestBySubregion($keyWordList, 'REGION', $category, $time, $property, $sleep);
    }

    /**
     * @param array $keyWordList
     * @param null $subregion
     * @param int $category
     * @param string $time
     * @param string $property
     * @param int $sleep
     * @return array|bool
     * @throws \Exception
     */
    public function interestByCity(array $keyWordList, $subregion=null, $category=0, $time='now 1-h', $property='', $sleep=0.5)
    {
        return $this->_interestBySubregion($keyWordList, 'CITY', $category, $time, $property, $sleep, $subregion);
    }

    /**
     * @param array $keyWordList
     * @param null $subregion
     * @param int $category
     * @param string $time
     * @param string $property
     * @param int $sleep
     * @return array|bool
     * @throws \Exception
     */
    public function interestByMetro(array $keyWordList, $subregion=null, $category=0, $time='now 1-h', $property='', $sleep=0.5)
    {
        return $this->_interestBySubregion($keyWordList, 'DMA', $category, $time, $property, $sleep, $subregion);
    }

    /**
     * @return array|bool
     * @throws \Exception
     */
    public function categories()
    {
        $uri = self::CATEGORIES_URL;
        $param = ['hl' => $this->options['hl']];
        $data = $this->_getData($uri, 'GET', $param);
        if ($data) {

            return Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY);
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
        $cookieJar = tempnam('/tmp','cookie');
        $client->setOptions([
            'adapter' => Http\Client\Adapter\Curl::class,
            'curloptions' => [
                CURLOPT_COOKIEJAR => $cookieJar,
            ],
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
        $client->setOptions([
            'curloptions' => [
                CURLOPT_COOKIEFILE => $cookieJar,
            ]]);
        $client->send();
        unlink($cookieJar);

        $statusCode = $client->getResponse()->getStatusCode();
        if ($statusCode == 200) {

            $headers = $client->getResponse()->getHeaders()->toArray();
            foreach ($headers as $header => $value) {

                if ($header == 'Content-Type') {

                    if (
                        (stripos($value, 'application/json') !== false OR
                            stripos($value, 'application/javascript') !== false OR
                            stripos($value, 'text/javascript') !== false) AND $client->getResponse()->getBody()
                    ) {

                        return $client->getResponse()->getBody();
                    }
                }
            }
        }
        return false;
    }
}
