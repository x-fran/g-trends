<?php

declare(strict_types=1);

namespace XFran\GTrends;

use Laminas\Http;
use Laminas\Json;

use function array_key_exists;
use function count;
use function in_array;
use function stripos;
use function substr;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

use const CURLOPT_COOKIEFILE;
use const CURLOPT_COOKIEJAR;

class GTrends
{
    private const GENERAL_ENDPOINT                  = 'https://trends.google.com/trends/api/explore';
    private const INTEREST_OVER_TIME_ENDPOINT       = 'https://trends.google.com/trends/api/widgetdata/multiline';
    private const RELATED_QUERIES_ENDPOINT          = 'https://trends.google.com/trends/api/widgetdata/relatedsearches';
    private const SUGGESTIONS_AUTOCOMPLETE_ENDPOINT = 'https://trends.google.com/trends/api/autocomplete';
    private const COMPARED_GEO_ENDPOINT             = 'https://trends.google.com/trends/api/widgetdata/comparedgeo';
    private const CATEGORIES_ENDPOINT               = 'https://trends.google.com/trends/api/explore/pickers/category';
    private const GEO_ENDPOINT                      = 'https://trends.google.com/trends/api/explore/pickers/geo';
    private const DAILY_SEARCH_TRENDS_ENDPOINT      = 'https://trends.google.com/trends/api/dailytrends';
    private const REAL_TIME_SEARCH_TRENDS_ENDPOINT  = 'https://trends.google.com/trends/api/realtimetrends';

    private array $options = [
        'hl'        => 'en-US',
        'tz'        => 0,
        'geo'       => 'US',
        'time'      => 'all',
        'category'  => 0,
    ];

    public function __construct(array $options = [])
    {
        if ($options) {
            $this->setOptions($options);
        }
    }

    public function getRealTimeSearchTrends($cat = 'all', $fi = 0, $fs = 0, $ri = 300, $rs = 20, $sort = 0)
    {
        $payload = [
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
        if ($dataJson = $this->getData(self::REAL_TIME_SEARCH_TRENDS_ENDPOINT, $payload)) {
            return Json\Json::decode(trim(substr($dataJson, 5)), Json\Json::TYPE_ARRAY);
        }
        return [];
    }

    public function getDailySearchTrends($ns = 15)
    {
        $payload = [
            'hl'    => $this->options['hl'],
            'tz'    => $this->options['tz'],
            'geo'   => $this->options['geo'],
            'ns'    => $ns,
        ];
        if ($dataJson = $this->getData(self::DAILY_SEARCH_TRENDS_ENDPOINT, $payload)) {
            return Json\Json::decode(trim(substr($dataJson, 5)), Json\Json::TYPE_ARRAY)['default'];
        }
        return [];
    }

    public function getGeo(): array
    {
        if ($data = $this->getData(self::GEO_ENDPOINT, ['hl' => $this->options['hl']])) {
            return Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY);
        }
        return [];
    }

    public function getCategories(): array
    {
        if ($data = $this->getData(self::CATEGORIES_ENDPOINT, ['hl' => $this->options['hl']])) {
            return Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY);
        }
        return [];
    }

    public function getSuggestionsAutocomplete(string $kWord): array
    {
        $uri = self::SUGGESTIONS_AUTOCOMPLETE_ENDPOINT . "/'$kWord'";
        if ($data = $this->getData($uri, ['hl' => $this->options['hl']])) {
            return Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY)['default'];
        }
        return [];
    }

    public function getAllOneKeyWord(string $kWord): array
    {
        return $this->explore([$kWord], ['RELATED_TOPICS', 'TIMESERIES', 'RELATED_QUERIES', 'GEO_MAP']);
    }

    public function getAllMultipleKeyWords(array $keyWords): array
    {
        return $this->explore($keyWords, ['TIMESERIES', 'RELATED_QUERIES', 'GEO_MAP']);
    }

    public function getRelatedTopics(string $kWord): array
    {
        return $this->explore([$kWord], ['RELATED_TOPICS']);
    }

    public function getInterestOverTime(array $keyWords): array
    {
        return $this->explore($keyWords, ['TIMESERIES']);
    }

    public function getRelatedSearchQueries(array $keyWords): array
    {
        return $this->explore($keyWords, ['RELATED_QUERIES']);
    }

    public function getComparedGeo(array $keyWords, string $resolution = 'CITY'): array
    {
        return $this->explore($keyWords, ['GEO_MAP'], $resolution);
    }

    private function explore(array $keyWords, array $widgetIds, string $resolution = 'CITY'): array
    {
        if (count($keyWords) > 5) {
            return [];
        }

        $comparisonItem = [];
        if (!$keyWords) {
            $comparisonItem[] = ['geo' => $this->options['geo'], 'time' => $this->options['time']];
        } else {
            foreach ($keyWords as $kWord) {
                $comparisonItem[]
                    = ['keyword' => $kWord, 'geo' => $this->options['geo'], 'time' => $this->options['time']];
            }
        }

        $payload = [
            'hl'    => $this->options['hl'],
            'tz'    => $this->options['tz'],
            'req'   => Json\Json::encode(
                ['comparisonItem' => $comparisonItem, 'category' => $this->options['category'], 'property' => '']
            ),
        ];


        $results = [];
        if ($data = $this->getData(self::GENERAL_ENDPOINT, $payload)) {
            $widgets = Json\Json::decode(trim(substr($data, 5)), Json\Json::TYPE_ARRAY)['widgets'];
            foreach ($widgets as $widget) {
                if (!array_key_exists('token', $widget)) {
                    continue;
                }

                $payload['hl']      = $this->options['hl'];
                $payload['tz']      = $this->options['tz'];
                $payload['req']     = str_replace('"geo":[]', '"geo":{}', Json\Json::encode($widget['request']));
                $payload['token']   = $widget['token'];

                unset(
                    $widget['showLegend'],
                    $widget['helpDialog'],
                    $widget['type'],
                    $widget['template'],
                    $widget['embedTemplate'],
                    $widget['isLong'],
                    $widget['isCurated'],
                    $widget['color'],
                    $widget['displayMode'],
                    $widget['bullets']
                );

                if ($widget['id'] === 'GEO_MAP' && in_array('GEO_MAP', $widgetIds, true)) {
                    $widget['request']['resolution'] = $resolution;
                    $widget['request']['includeLowSearchVolumeGeos'] = false;
                    if ($data = $this->getData(self::COMPARED_GEO_ENDPOINT, $payload)) {
                        $results['GEO_MAP']['widget']   = $widget;
                        $results['GEO_MAP']['data']     =
                            Json\Json::decode(
                                trim(substr($data, 5)),
                                Json\Json::TYPE_ARRAY
                            )['default'];
                    }
                }

                if (
                    in_array('RELATED_QUERIES', $widgetIds, true)
                    && stripos($widget['id'], 'RELATED_QUERIES') !== false
                    && $data = $this->getData(self::RELATED_QUERIES_ENDPOINT, $payload)
                ) {
                    $results['RELATED_QUERIES']['widget'][] = $widget;
                    $results['RELATED_QUERIES']['data'][]   =
                        Json\Json::decode(
                            trim(substr($data, 5)),
                            Json\Json::TYPE_ARRAY
                        )['default'];
                }

                if (
                    in_array('RELATED_TOPICS', $widgetIds, true)
                    && stripos($widget['id'], 'RELATED_TOPICS') !== false
                    && $data = $this->getData(self::RELATED_QUERIES_ENDPOINT, $payload)
                ) {
                    $results['RELATED_TOPICS']['widget']    = $widget;
                    $results['RELATED_TOPICS']['data']      =
                        Json\Json::decode(
                            trim(substr($data, 5)),
                            Json\Json::TYPE_ARRAY
                        )['default'];
                }

                if (
                    in_array('TIMESERIES', $widgetIds, true)
                    && stripos($widget['id'], 'TIMESERIES') !== false
                    && $data = $this->getData(self::INTEREST_OVER_TIME_ENDPOINT, $payload)
                ) {
                    $results['TIMESERIES']['widget']    = $widget;
                    $results['TIMESERIES']['data']      =
                        Json\Json::decode(
                            trim(substr($data, 5)),
                            Json\Json::TYPE_ARRAY
                        )['default'];
                }
            }
        }
        return $results;
    }

    private function getData(string $uri, array $payload): string
    {
        $client = new Http\Client();
        $cookieJar = tempnam(sys_get_temp_dir(), 'cookie');
        $client->setOptions([
            'adapter'       => Http\Client\Adapter\Curl::class,
            'curloptions'   => [
                CURLOPT_COOKIEJAR => $cookieJar,
            ],
            'maxredirects' => 10,
            'timeout' => 100,
        ]);
        $client->setUri($uri);
        $client->setMethod('GET');

        if ($payload) {
            $client->setParameterGet($payload);
        }

        $client->send();
        $client->setOptions([
            'curloptions' => [
                CURLOPT_COOKIEFILE => $cookieJar,
            ],
        ]);
        $client->send();
        unlink($cookieJar);

        if ($client->getResponse()->getStatusCode() === 200) {
            return $client->getResponse()->getBody();
        }
        return '';
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): GTrends
    {
        $this->options = $options;
        return $this;
    }
}
