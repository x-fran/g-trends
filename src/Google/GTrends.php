<?php

namespace Google;

use Laminas\Json;
use Laminas\Http;
use Laminas\Stdlib;

class GTrends
{
    const GENERAL_ENDPOINT = 'https://trends.google.com/trends/api/explore';
    const DAILY_SEARCH_TRENDS_ENDPOINT = 'https://trends.google.com/trends/api/dailytrends';
    const INTEREST_OVER_TIME_ENDPOINT = 'https://trends.google.com/trends/api/widgetdata/multiline';
    const RELATED_QUERIES_ENDPOINT = 'https://trends.google.com/trends/api/widgetdata/relatedsearches';
    const REAL_TIME_SEARCH_TRENDS_ENDPOINT = 'https://trends.google.com/trends/api/realtimetrends';
    const SUGGESTIONS_AUTOCOMPLETE_ENDPOINT = 'https://trends.google.com/trends/api/autocomplete';
    const INTEREST_BY_SUBREGION_ENDPOINT = 'https://trends.google.com/trends/api/widgetdata/comparedgeo';
    const CATEGORIES_ENDPOINT = 'https://trends.google.com/trends/api/explore/pickers/category';
    const TOP_CHARTS_CATEGORY_ENDPOINT = 'https://trends.google.com/trends/topcharts/category';

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
     * @param int $ns
     * @return mixed
     */
    public function getDailySearchTrends($ns=15)
    {
        $params =[
            'hl'    => $this->options['hl'],
            'tz'    => $this->options['tz'],
            'geo'   => $this->options['geo'],
            'ns'    => $ns,
        ];

        try {

            $dataJson = $this->_getData(self::DAILY_SEARCH_TRENDS_ENDPOINT, 'GET', $params);
            return Json\Json::decode(trim(substr($dataJson, 5)), Json\Json::TYPE_ARRAY);
        } catch (\Exception $e) {

            die($e->getMessage());
        }
    }

    /**
     * @param string $cat
     * @param int $fi
     * @param int $fs
     * @param int $ri
     * @param int $rs
     * @param int $sort
     * @return mixed
     */
    public function getRealTimeSearchTrends($cat='all', $fi=0, $fs=0, $ri=300, $rs=20, $sort=0)
    {
        $params =[
            'hl'    => $this->options['hl'],
            'tz'    => $this->options['tz'],
            'cat'   => $cat,
            'fi'    => $fi,
            'fs'    => $fs,
            'geo'   => $this->options['geo'],
            'ri'    => $ri,
            'rs'    => $rs,
            'sort'  => $sort,
        ];

        try {

            $dataJson = $this->_getData(self::REAL_TIME_SEARCH_TRENDS_ENDPOINT, 'GET', $params);
            return Json\Json::decode(trim(substr($dataJson, 5)), Json\Json::TYPE_ARRAY);
        } catch (\Exception $e) {

            die($e->getMessage());
        }
    }

    /**
     * @param null $keyWordList
     * @param int $category
     * @param string $time
     * @param string $property
     * @param float $sleep
     * @return array|bool|mixed
     * @throws \Exception
     */
    public function getRelatedSearchQueries($keyWordList=null, $category=0, $time='today 12-m', $property='', $sleep=0.5)
    {
        if (null !== $keyWordList && !is_array($keyWordList)) {
            $keyWordList = [$keyWordList];
        }

        $timeInfo = explode('-', $time);
        $timeInfo[0] = strtolower($timeInfo[0]);
        $timeInfo[1] = strtolower($timeInfo[1]);
        $time = implode('-', $timeInfo);

        if (null === $keyWordList) {
            $comparisonItem[] = ['geo' => $this->options['geo'], 'time' => $time];
        } else {
            if (count($keyWordList) > 5) {

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

        $data = $this->_getData(self::GENERAL_ENDPOINT, 'GET', $payload);
        if ($data) {

            $widgetsArray = Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY)['widgets'];

            $results = [];
            foreach ($widgetsArray as $widget) {

                if (stripos($widget['id'], 'RELATED_QUERIES') !== false) {

                    $kWord = $widget['request']['restriction']['complexKeywordsRestriction']['keyword'][0]['value'] ?? null;
                    $relatedPayload['hl'] = $this->options['hl'];
                    $relatedPayload['tz'] = $this->options['tz'];
                    $relatedPayload['req'] = Json\Json::encode($widget['request']);
                    $relatedPayload['token'] = $widget['token'];
                    $data = $this->_getData(self::RELATED_QUERIES_ENDPOINT, 'GET', $relatedPayload);

                    if ($data) {

                        $queriesArray = Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY);

                        if (null === $kWord) {
                            $results = $queriesArray;
                        } else {
                            $results[$kWord] = $queriesArray;
                        }

                        if ($keyWordList and count($keyWordList)>1) {

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
     * @param $keyWordList
     * @param int $category
     * @param string $time
     * @param string $property
     * @param array $widgetIds
     * @param float $sleep
     * @return array|bool
     * @throws \Exception
     */
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

        $data = $this->_getData(self::GENERAL_ENDPOINT, 'GET', $payload);

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

                $data = $this->_getData(self::INTEREST_OVER_TIME_ENDPOINT, 'GET', $interestOverTimePayload);
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

                $data = $this->_getData(self::INTEREST_BY_SUBREGION_ENDPOINT, 'GET', $interestBySubregionPayload);
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
                $data = $this->_getData(self::RELATED_QUERIES_ENDPOINT, 'GET', $relatedPayload);
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

                $data = $this->_getData(self::RELATED_QUERIES_ENDPOINT, 'GET', $relatedPayload);
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
    public function interestOverTime($kWord, $category=0, $time='now 4-H', $property='')
    {
        $payload = [
            'hl' => $this->options['hl'],
            'tz' => $this->options['tz'],
            'req' => Json\Json::encode([
                'comparisonItem' => [
                    [
                        'keyword' => $kWord,
                        'geo' => $this->options['geo'],
                        'time' => $time,
                    ],
                ],
                'category' => $category,
                'property' => $property,
            ]),
        ];
        $data = $this->_getData(self::GENERAL_ENDPOINT, 'GET', $payload);
        if ($data) {

            $widgets = Json\Json::decode(trim(substr($data, 4)), Json\Json::TYPE_OBJECT)->widgets;

            foreach ($widgets as $widget) {

                if ($widget->id == 'TIMESERIES') {

                    $interestOverTimePayload['hl'] = $this->options['hl'];
                    $interestOverTimePayload['tz'] = $this->options['tz'];
                    $interestOverTimePayload['req'] = Json\Json::encode($widget->request);
                    $interestOverTimePayload['token'] = $widget->token;

                    $data = $this->_getData(self::INTEREST_OVER_TIME_ENDPOINT, 'GET', $interestOverTimePayload);
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
     * @param null $kWord
     * @param int $category
     * @param string $time
     * @param string $property
     * @return bool|mixed
     */
    public function getRelatedTopics($kWord=null, $category=0, $time='today 12-m', $property='')
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

        $data = $this->_getData(self::GENERAL_ENDPOINT, 'GET', $payload);
        if ($data) {

            $widgetsArray = Json\Json::decode(trim(substr($data, 4)), Json\Json::TYPE_ARRAY)['widgets'];

            foreach ($widgetsArray as $widget) {
                if ($widget['id'] === 'RELATED_TOPICS') {
                    $relatedPayload['hl'] = $this->options['hl'];
                    $relatedPayload['tz'] = $this->options['tz'];
                    $relatedPayload['req'] = Json\Json::encode($widget['request']);
                    $relatedPayload['token'] = $widget['token'];

                    $data = $this->_getData(self::RELATED_QUERIES_ENDPOINT, 'GET', $relatedPayload);
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

  /**
   * @param array $keyWordList
   * @param $resolution
   * @param int $category
   * @param string $time
   * @param string $property
   * @param float $sleep
   * @param bool $disableTimeParsing
   *
   * @return array|bool
   * @throws \Exception
   */
    private function _interestBySubregion(array $keyWordList, $resolution, $category=0, $time='now 1-h', $property='', $sleep=0.5, $disableTimeParsing = false)
    {
        if (count($keyWordList) == 0 or count($keyWordList) > 5) {

            throw new \Exception('Invalid number of items provided in keyWordList');
        }

        if(!$disableTimeParsing) {
          $timeInfo = explode('-', $time);
          $timeInfo[0] = strtolower($timeInfo[0]);
          $timeUnit = array_pop($timeInfo);
          $timeInfo[] = strtoupper($timeUnit);
          $time = implode('-', $timeInfo);
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

        $data = $this->_getData(self::GENERAL_ENDPOINT, 'GET', $payload);
        if ($data) {

            $widgetsArray = Json\Json::decode(trim(substr($data, 5)), true)['widgets'];

            $results = [];
            foreach ($widgetsArray as $widget) {

                if (strpos($widget['id'], 'GEO_MAP') !== false and key_exists('bullet', $widget)) {

                    $widget['request']['resolution'] = strtoupper($resolution);

                    $interestBySubregionPayload['hl'] = $this->options['hl'];
                    $interestBySubregionPayload['tz'] = $this->options['tz'];
                    $interestBySubregionPayload['req'] = Json\Json::encode($widget['request']);
                    $interestBySubregionPayload['token'] = $widget['token'];

                    $data = $this->_getData(self::INTEREST_BY_SUBREGION_ENDPOINT, 'GET', $interestBySubregionPayload);
                    if ($data) {

                        $queriesArray = Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY);

                        if ($queriesArray['default']['geoMapData']) {
                            $results[$widget['bullet']] = $queriesArray['default']['geoMapData'];
                        }
                    }
                }

                if (count($keyWordList) > 1) {

                    sleep($sleep);
                }
            }

            return $results;
        }

        return false;
    }

  /**
   * @param array $keyWordList
   * @param int $category
   * @param string $time
   * @param string $property
   * @param float $sleep
   * @param bool $disableTimeParsing
   *
   * @return array|bool
   */
    public function interestBySubregion(array $keyWordList, $category=0, $time='now 1-h', $property='', $sleep=0.5, $disableTimeParsing = false)
    {
        try {
            return $this->_interestBySubregion($keyWordList, 'SUBREGION', $category, $time, $property, $sleep, $disableTimeParsing);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

  /**
   * @param array $keyWordList
   * @param int $category
   * @param string $time
   * @param string $property
   * @param float $sleep
   * @param bool $disableTimeParsing
   *
   * @return array|bool
   */
    public function interestByCity(array $keyWordList, $category=0, $time='now 1-h', $property='', $sleep=0.5, $disableTimeParsing = false)
    {
        try {
            return $this->_interestBySubregion($keyWordList, 'CITY', $category, $time, $property, $sleep, $disableTimeParsing);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

  /**
   * @param array $keyWordList
   * @param int $category
   * @param string $time
   * @param string $property
   * @param float $sleep
   * @param bool $disableTimeParsing
   *
   * @return array|bool
   */
    public function interestByMetro(array $keyWordList, $category=0, $time='now 1-h', $property='', $sleep=0.5, $disableTimeParsing = false)
    {
        try {
            return $this->_interestBySubregion($keyWordList, 'DMA', $category, $time, $property, $sleep, $disableTimeParsing);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

  /**
   * @param array $keyWordList
   * @param int $category
   * @param string $time
   * @param string $property
   * @param float $sleep
   * @param bool $disableTimeParsing
   *
   * @return array|bool
   */
    public function interestByRegion(array $keyWordList, $category=0, $time='now 1-h', $property='', $sleep=0.5, $disableTimeParsing = false)
    {
        try {
            return $this->_interestBySubregion($keyWordList, 'REGION', $category, $time, $property, $sleep, $disableTimeParsing);
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }


    /**
     * @param $kWord
     *
     * @return array|bool
     */
    public function suggestionsAutocomplete($kWord)
    {
        $uri = self::SUGGESTIONS_AUTOCOMPLETE_ENDPOINT . "/'$kWord'";
        $param = ['hl' => $this->options['hl']];
        $data = $this->_getData($uri, 'GET', $param);
        if ($data) {

            return Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY);
        }
        return false;
    }

    /**
     * @return array|bool
     * @throws \Exception
     */
    public function getCategories()
    {
        $uri = self::CATEGORIES_ENDPOINT;
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
     */
    private function _getData($uri, $method, array $params=[])
    {
        if ($method != 'GET' AND $method != 'POST') {

            # throw new \Exception(__METHOD__ . " $method method not allowed");
            die(__METHOD__ . " $method method not allowed");
        }

        $client = new Http\Client();
        $cookieJar = tempnam(sys_get_temp_dir(),'cookie');
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
