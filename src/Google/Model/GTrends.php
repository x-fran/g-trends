<?php

namespace Google\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Google\Entity\Keyword;
use Google\Entity\Row;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Class GTrends
 * @package Google
 */
class GTrends
{
    private const BASE_API_URL = 'https://trends.google.com/trends/api/';
    private const EXPLORE = 'explore';//https://trends.google.com/trends/api/
    private const RELATED_QUERIES = 'widgetdata/relatedsearches';//https://trends.google.com/trends/api/
    private const INTEREST_OVER_TIME = 'widgetdata/multiline';//https://trends.google.com/trends/api/
    private const TRENDING_SEARCHES_URL = 'https://trends.google.com/trends/hottrends/hotItems';
    private const TOP_CHARTS_URL = 'https://trends.google.com/trends/topcharts/chart';
    private const SUGGESTIONS = 'autocomplete';//https://trends.google.com/trends/api/
    private const INTEREST_BY_SUBREGION = 'widgetdata/comparedgeo';//https://trends.google.com/trends/api/
    private const LATEST_STORIES = 'stories/latest';

    private const MAX_KEYWORDS_COMPARING = 5;
    private const MIN_KEYWORDS_COMPARING = 1;

    protected $language = 'en-US';

    /**
     * UTC is equal to 0.
     * Another example: 360 is equal to UTC-6
     * @var int
     */
    protected $timezone = 0;

    /**
     * The empty string for geo location is INTERNATIONAL
     * @var string
     */
    protected $geoLocation = '';

    /**
     * Time in seconds to sleep the process before doing a new request
     * @var int
     */
    private $sleep = 5;

    /**
     * @var Client
     */
    private $client;

    /**
     * GTrends constructor.
     * @param mixed[] $options
     */
    public function __construct(array $options=[])
    {
        $this->setOptions($options);
        $this->client = new Client([
            'base_uri' => self::BASE_API_URL,
            'timeout' => 100,
            'allow_redirects' => ['max' => 5]
        ]);
    }

    /**
     * @return mixed[]
     */
    public function getOptions(): array
    {
        return [
            'hl'  => $this->language,
            'tz'  => $this->timezone,
            'geo' => $this->geoLocation
        ];
    }

    /**
     * @param mixed[] $options
     * @return self
     */
    public function setOptions(array $options): self
    {
        if (isset($options['tz'])) {
            $this->timezone = (int)$options['tz'];
        }

        if (isset($options['hl'])) {
            $this->language = (string)$options['hl'];
        }

        if (isset($options['geo'])) {
            $this->geoLocation = (string)$options['geo'];
        }

        return $this;
    }

    /**
     * @param array $keyWordList
     * @param int $category
     * @param string $time
     * @param string $property
     * @return ArrayCollection
     * @throws \Exception
     */
    public function relatedQueries(array $keyWordList, int $category=0, string $time='now 1-H', string $property='') : ArrayCollection
    {
        $totalKeyWordList = count($keyWordList);
        if ($totalKeyWordList == 0 || $totalKeyWordList > 5) {

            throw new \Exception('Invalid number of items provided in keyWordList');
        }

        $comparisonItem = [];
        foreach ($keyWordList as $kWord) {
            $comparisonItem[] = ['keyword' => $kWord, 'geo' => $this->geoLocation, 'time' => $time];
        }

        $response = $this->client->get(self::EXPLORE, [
            'query' => [
                'hl' => $this->language,
                'tz' => $this->timezone,
                'req' => json_encode(['comparisonItem' => $comparisonItem, 'category' => $category, 'property' => $property]),
            ]
        ]);

        $object = $this->decodeResponse($response);
        if(!is_object($object)){
            throw new \Exception('Invalid response, the given response does not contain a valid JSON');
        }

        $results = new ArrayCollection();
        foreach ($object->widgets as $widget) {

            if ($widget->title != 'Related queries') {
                continue;
            }

            $kWord = $widget->request->restriction->complexKeywordsRestriction->keyword[0]->value;

            $response = $this->client->get(self::RELATED_QUERIES, [
                'query' => [
                    'hl' => $this->language,
                    'tz' => $this->timezone,
                    'req' => json_encode($widget->request),
                    'token' => $widget->token
                ]
            ]);

            $queriesArray = $this->decodeResponse($response);
            $results->set($kWord, $queriesArray);

            if ($totalKeyWordList > 1) {
                sleep($this->sleep);
            }
        }

        return $results;
    }

    /**
     * @param string $keyword
     * @param int    $category
     * @param string $time
     * @param string $property
     * @return Keyword
     * @throws \Exception
     */
    public function interestOverTime(string $keyword, int $category=0, string $time='now 1-H', string $property='') : Keyword
    {
        return $this->comparingInterestOverTime([$keyword], $category, $time, $property)
                        ->get($keyword);
    }

    /**
     * @param string[] $kWordList
     * @param int    $category
     * @param string $time
     * @param string $property
     * @return ArrayCollection
     * @throws \Exception
     */
    public function comparingInterestOverTime(array $kWordList, int $category=0, string $time='now 1-H', string $property='') : ArrayCollection
    {
        $count = count($kWordList);
        if($count > self::MAX_KEYWORDS_COMPARING || $count < self::MIN_KEYWORDS_COMPARING){
            throw new \Exception('Invalid number of items provided in keyWordList');
        }

        $comparisonItem = [];
        foreach($kWordList as $kWord)
            $comparisonItem[] = ['keyword' => $kWord, 'geo' => $this->geoLocation, 'time' => $time];

        $response = $this->client->get(self::EXPLORE, [
            'query' => [
                'hl' => $this->language,
                'tz' => $this->timezone,
                'req' => json_encode(['comparisonItem' => $comparisonItem, 'category' => $category, 'property' => $property]),
            ]
        ]);

        $object = $this->decodeResponse($response, 4);
        if(!is_object($object)){
            throw new \Exception('Invalid response, the given response does not contain a valid JSON');
        }

        $results = new ArrayCollection();
        foreach ($object->widgets as $widget) {

            if ($widget->title != 'Interest over time') {
                continue;
            }

            $this->prepareKeywordList($results, $widget->bullets);

            $response = $this->client->get(self::INTEREST_OVER_TIME, [
                'query' => [
                    'hl' => $this->language,
                    'tz' => $this->timezone,
                    'req' => json_encode($widget->request),
                    'token' => $widget->token
                ]
            ]);

            $object = $this->decodeResponse($response);
            $this->fillResponseValueRows($results, $widget->bullets, $object->default->timelineData);

            return $results;
        }

        return $results;
    }

    /**
     * @param ArrayCollection $collection
     * @param array $rawList
     * @param array $timeLineData
     */
    private function fillResponseValueRows(ArrayCollection $collection, array $rawList, array $timeLineData) : void
    {
        foreach ($timeLineData as $object){

            foreach($object->value as $position => $value){

                /* @var $keyword Keyword*/
                $keyword = $collection->get($rawList[$position]->text);
                $row = new Row($object->time, $value);

                $keyword->addRow($row);

            }
        }
    }

    /**
     * @param ArrayCollection $collection
     * @param array $rawList
     */
    private function prepareKeywordList(ArrayCollection $collection, array $rawList) : void
    {
        foreach($rawList as $object){
            if($collection->containsKey($object->text)){
                continue;
            }

            $keyword = new Keyword($object->text);
            $collection->set($keyword->getValue(), $keyword);
        }
    }

    /**
     * @param string $country
     * @param string $date
     * @return array
     * @throws \Exception
     */
    public function trendingSearches(string $country, string $date)
    {
        $response =  $this->client->post(self::TRENDING_SEARCHES_URL, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'form_params' => [
                'ajax' => '1',
                'pn' => $country,
                'htd' => '',
                'htv' => 'l',
                'std' => $date,
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * @param string $date
     * @param string $cid
     * @param string $geo
     * @param string $cat
     *
     * @return array|bool
     * @throws \Exception
     */
    public function topCharts(string $date, string $cid, string $geo='US', string $cat='')
    {
        $response = $this->client->get(self::TOP_CHARTS_URL, [
            'query' => [
                'ajax' => 1,
                'lp' => 1,
                'geo' => $geo,
                'date' => $date,
                'cat' => $cat,
                'cid' => $cid,
            ]
        ]);

        return json_decode(trim($response->getBody()), true);
    }

    /**
     * @param string $kWord
     * @return array|bool
     * @throws \Exception
     */
    public function suggestionsAutocomplete(string $kWord)
    {
        $response = $this->client->get(self::SUGGESTIONS . "/'{$kWord}'", [
            'query' => [
                'hl' => $this->language
            ]
        ]);

        return $this->decodeResponse($response);
    }

    /**
     * @param array $keyWordList
     * @param string $resolution
     * @param int $category
     * @param string $time
     * @param string $property
     * @return array|bool
     * @throws \Exception
     */
    public function interestBySubregion(array $keyWordList, $resolution='SUBREGION', $category=0, $time='now 1-H', $property='')
    {
        $count = count($keyWordList);
        if($count > self::MAX_KEYWORDS_COMPARING || $count < self::MIN_KEYWORDS_COMPARING){
            throw new \Exception('Invalid number of items provided in keyWordList');
        }

        $comparisonItem = [];
        foreach ($keyWordList as $kWord) {
            $comparisonItem[] = ['keyword' => $kWord, 'geo' => $this->geoLocation, 'time' => $time];
        }

        $response = $this->client->get(self::EXPLORE, [
            'query' => [
                'hl' => $this->language,
                'tz' => $this->timezone,
                'req' => json_encode(['comparisonItem' => $comparisonItem, 'category' => $category, 'property' => $property]),
            ]
        ]);

        $object = $this->decodeResponse($response);

        $results = [];
        foreach ($object->widgets as $widget) {

            if ($widget->title != 'Interest by subregion') {
                continue;
            }

            $kWord = $widget->bullet;
            if (empty($this->geoLocation)) {
                $widget->request->resolution = ucfirst(strtolower($resolution));
            }

            $response = $this->client->get(self::INTEREST_BY_SUBREGION, [
                'query' => [
                    'hl' => $this->language,
                    'tz' => $this->timezone,
                    'req' => json_encode($widget['request']),
                    'token' => $widget['token']
                ]
            ]);

            $results[$kWord] = $this->decodeResponse($response);

            if (count($keyWordList)>1) {
                sleep($this->sleep);
            }
        }

        return $results;
    }

    /**
     * @param string $country
     * @param string $cat
     * @param string $geo
     * @param int    $tz
     * @return bool|mixed
     */
    public function latestStories($country='en-US', $cat='all', $geo='IE', $tz=-60)
    {
        $response = $this->client->get(self::LATEST_STORIES, [
            'query' => [
                'hl' => $country,
                'cat' => $cat,
                'fi' => 15,
                'fs' => 15,
                'geo' => $geo,
                'ri' => 300,
                'rs' => 15,
                'tz' => $tz,
            ]
        ]);

        return $this->decodeResponse($response, 4);
    }

    /**
     * @param ResponseInterface $response
     * @param int $start "Used to remove the first N characters from Google response"
     * @return mixed
     */
    private function decodeResponse(ResponseInterface $response, int $start = 5)
    {
        return json_decode(trim(substr($response->getBody(), $start)));
    }
}
