<?php

declare(strict_types=1);

namespace XFran\GTrends;

use InvalidArgumentException;
use Laminas\Http;
use SimpleXMLElement;

use function array_key_exists;
use function array_map;
use function count;
use function in_array;
use function is_file;
use function is_numeric;
use function is_scalar;
use function is_string;
use function is_array;
use function json_decode;
use function json_encode;
use function preg_match;
use function rawurlencode;
use function simplexml_load_string;
use function str_replace;
use function substr;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

use const CURLOPT_COOKIEFILE;
use const CURLOPT_COOKIEJAR;
use const JSON_THROW_ON_ERROR;

/**
 * Unofficial Google Trends client.
 *
 * Every method talks to undocumented trends.google.com endpoints and throws
 * GTrendsException when Google answers with anything other than the expected payload.
 */
final class GTrends
{
    public const string TIMESERIES      = 'TIMESERIES';
    public const string GEO_MAP         = 'GEO_MAP';
    public const string RELATED_QUERIES = 'RELATED_QUERIES';
    public const string RELATED_TOPICS  = 'RELATED_TOPICS';

    public const array WIDGETS = [self::TIMESERIES, self::GEO_MAP, self::RELATED_QUERIES, self::RELATED_TOPICS];

    public const array TRENDING_HOURS = [4, 24, 48, 168];

    /** Topic ids of Google's "Trending Now" page (community-documented, not official). */
    public const array TRENDING_TOPICS = [
        1  => 'Autos and Vehicles',
        2  => 'Beauty and Fashion',
        3  => 'Business and Finance',
        4  => 'Entertainment',
        5  => 'Food and Drink',
        6  => 'Games',
        7  => 'Health',
        8  => 'Hobbies and Leisure',
        9  => 'Jobs and Education',
        10 => 'Law and Government',
        11 => 'Other',
        13 => 'Pets and Animals',
        14 => 'Politics',
        15 => 'Science',
        16 => 'Shopping',
        17 => 'Sports',
        18 => 'Technology',
        19 => 'Travel and Transportation',
        20 => 'Climate',
    ];

    private const string EXPLORE_ENDPOINT      = 'https://trends.google.com/trends/api/explore';
    private const string CATEGORIES_ENDPOINT   = 'https://trends.google.com/trends/api/explore/pickers/category';
    private const string GEO_ENDPOINT          = 'https://trends.google.com/trends/api/explore/pickers/geo';
    private const string AUTOCOMPLETE_ENDPOINT = 'https://trends.google.com/trends/api/autocomplete';
    private const string TRENDING_ENDPOINT     = 'https://trends.google.com/_/TrendsUi/data/batchexecute';
    private const string TRENDING_RSS_ENDPOINT = 'https://trends.google.com/trending/rss';

    /** Widget type => endpoint that serves its data, given the widget token from EXPLORE_ENDPOINT. */
    private const array WIDGET_ENDPOINTS = [
        self::TIMESERIES      => 'https://trends.google.com/trends/api/widgetdata/multiline',
        self::GEO_MAP         => 'https://trends.google.com/trends/api/widgetdata/comparedgeo',
        self::RELATED_QUERIES => 'https://trends.google.com/trends/api/widgetdata/relatedsearches',
        self::RELATED_TOPICS  => 'https://trends.google.com/trends/api/widgetdata/relatedsearches',
    ];

    /** RPC id of the "Trending Now" list on trends.google.com/trending */
    private const string TRENDING_RPC_ID = 'i0OFE';

    private const int MAX_KEYWORDS = 5;

    /**
     * @param string     $hl       Interface language, e.g. en-US
     * @param int        $tz       Timezone offset in minutes, e.g. 360 for US CST
     * @param string     $geo      Country code, or '' for worldwide
     * @param string     $time     Explore time range: 'all', 'now 1-H', 'today 3-m', 'today 5-y', ...
     * @param int        $category Explore category id, 0 for all
     * @param array<string, mixed>|null $proxy Laminas Http\Client proxy options
     *                                          (proxy_host, proxy_port, proxy_user, proxy_pass)
     */
    public function __construct(
        private readonly string $hl = 'en-US',
        private readonly int $tz = 0,
        private readonly string $geo = 'US',
        private readonly string $time = 'all',
        private readonly int $category = 0,
        private readonly ?array $proxy = null,
    ) {
    }

    /**
     * Trends from trends.google.com/trending for the configured geo (replaces the retired
     * dailytrends and realtimetrends endpoints).
     *
     * @param int      $hours Time window, one of TRENDING_HOURS
     * @param int|null $topic Restrict to a TRENDING_TOPICS id
     * @return list<array{keyword: string, geo: string, startedAt: int, searchVolume: int,
     *                    volumeGrowthPercent: int, relatedQueries: list<string>, topics: list<int>,
     *                    topicNames: list<string>, newsArticleIds: list<int>}>
     */
    public function getTrendingNow(int $hours = 24, ?int $topic = null): array
    {
        if (!in_array($hours, self::TRENDING_HOURS, true)) {
            throw new InvalidArgumentException('$hours must be one of ' . implode(', ', self::TRENDING_HOURS));
        }
        if ($topic !== null && !array_key_exists($topic, self::TRENDING_TOPICS)) {
            throw new InvalidArgumentException("Unknown topic id $topic, see GTrends::TRENDING_TOPICS");
        }

        $trends = [];
        foreach ($this->fetchTrendingList($hours) as $t) {
            $trend = is_array($t) ? self::normaliseTrend($t) : null;
            if ($trend !== null && ($topic === null || in_array($topic, $trend['topics'], true))) {
                $trends[] = $trend;
            }
        }
        return $trends;
    }

    /**
     * Call the Trending Now RPC and unwrap its batchexecute envelope:
     * [["wrb.fr", "i0OFE", "<json string>", ...]] where the string decodes to [meta, trends].
     *
     * @return array<mixed> positional trend rows
     */
    private function fetchTrendingList(int $hours): array
    {
        $rpcArgs = json_encode([null, null, $this->geo, 0, $this->hl, $hours, 1], JSON_THROW_ON_ERROR);
        $raw = $this->request(
            self::TRENDING_ENDPOINT,
            ['rpcids' => self::TRENDING_RPC_ID, 'hl' => $this->hl],
            ['f.req' => json_encode([[[self::TRENDING_RPC_ID, $rpcArgs, null, 'generic']]], JSON_THROW_ON_ERROR)],
            withCookies: false,
        );

        $inner   = $this->decode($raw, self::TRENDING_ENDPOINT)[0][2] ?? null;
        $payload = is_string($inner) ? json_decode($inner, true) : null;
        $list    = is_array($payload) ? ($payload[1] ?? null) : null;
        if (!is_array($list)) {
            throw GTrendsException::unexpectedResponse(self::TRENDING_ENDPOINT, 'no trend list in RPC payload');
        }
        return $list;
    }

    /**
     * Map one positional trend row of the Trending Now RPC to named keys.
     * Observed layout: [0] keyword, [2] geo, [3] [startedAt], [6] search volume, [8] growth %,
     * [9] related queries, [10] topic ids, [11] news article refs as [id, lang, geo].
     *
     * @param array<mixed> $t
     * @return array{keyword: string, geo: string, startedAt: int, searchVolume: int,
     *               volumeGrowthPercent: int, relatedQueries: list<string>, topics: list<int>,
     *               topicNames: list<string>, newsArticleIds: list<int>}
     */
    private static function normaliseTrend(array $t): array
    {
        $topics   = self::intList($t[10] ?? null);
        $articles = is_array($t[11] ?? null) ? $t[11] : [];
        return [
            'keyword'             => self::str($t[0] ?? null),
            'geo'                 => self::str($t[2] ?? null),
            'startedAt'           => self::int(is_array($t[3] ?? null) ? $t[3][0] ?? null : null),
            'searchVolume'        => self::int($t[6] ?? null),
            'volumeGrowthPercent' => self::int($t[8] ?? null),
            'relatedQueries'      => self::stringList($t[9] ?? null),
            'topics'              => $topics,
            'topicNames'          => array_map(self::topicName(...), $topics),
            'newsArticleIds'      => self::intList(array_map(
                static fn (mixed $a) => is_array($a) ? $a[0] ?? null : null,
                $articles
            )),
        ];
    }

    /**
     * Google's public "Trending Now" RSS feed. Fewer fields than getTrendingNow() but it includes
     * news headlines and it is the only trending feed Google exposes on purpose.
     *
     * @return list<array{title: string, approxTraffic: string, pubDate: string, picture: string,
     *                    pictureSource: string, newsItems: list<array<string, string>>}>
     */
    public function getTrendingNowRss(): array
    {
        $rss = simplexml_load_string($this->request(self::TRENDING_RSS_ENDPOINT, ['geo' => $this->geo]));
        if (!$rss instanceof SimpleXMLElement) {
            throw GTrendsException::unexpectedResponse(self::TRENDING_RSS_ENDPOINT, 'invalid XML');
        }

        $items = [];
        foreach ($rss->channel->item as $item) {
            $ht = $item->children('ht', true);
            $newsItems = [];
            foreach ($ht->news_item as $news) {
                $newsItems[] = [
                    'title'   => (string) $news->news_item_title,
                    'snippet' => (string) $news->news_item_snippet,
                    'url'     => (string) $news->news_item_url,
                    'picture' => (string) $news->news_item_picture,
                    'source'  => (string) $news->news_item_source,
                ];
            }
            $items[] = [
                'title'         => (string) $item->title,
                'approxTraffic' => (string) $ht->approx_traffic,
                'pubDate'       => (string) $item->pubDate,
                'picture'       => (string) $ht->picture,
                'pictureSource' => (string) $ht->picture_source,
                'newsItems'     => $newsItems,
            ];
        }
        return $items;
    }

    /** @return array<mixed> Google's raw geo picker payload */
    public function getGeo(): array
    {
        return $this->decode($this->request(self::GEO_ENDPOINT, ['hl' => $this->hl]), self::GEO_ENDPOINT);
    }

    /** @return array<mixed> Google's raw category picker payload */
    public function getCategories(): array
    {
        return $this->decode($this->request(self::CATEGORIES_ENDPOINT, ['hl' => $this->hl]), self::CATEGORIES_ENDPOINT);
    }

    /** @return list<array{mid: string, title: string, type: string}> */
    public function getSuggestions(string $keyword): array
    {
        $uri = self::AUTOCOMPLETE_ENDPOINT . "/'" . rawurlencode($keyword) . "'";
        return $this->decode($this->request($uri, ['hl' => $this->hl]), $uri)['default']['topics'] ?? [];
    }

    /**
     * @param string|array<string> $keywords
     * @return array<mixed> Google's raw TIMESERIES widget data (timelineData, averages)
     */
    public function getInterestOverTime(string|array $keywords): array
    {
        return $this->explore((array) $keywords, [self::TIMESERIES])[self::TIMESERIES] ?? [];
    }

    /**
     * @param string|array<string> $keywords
     * @return array<string, array<mixed>> Google's raw RELATED_QUERIES widget data, keyed by keyword
     */
    public function getRelatedQueries(string|array $keywords): array
    {
        return $this->explore((array) $keywords, [self::RELATED_QUERIES])[self::RELATED_QUERIES] ?? [];
    }

    /**
     * Google only serves related topics for single-keyword queries.
     *
     * @return array<mixed> Google's raw RELATED_TOPICS widget data
     */
    public function getRelatedTopics(string $keyword): array
    {
        return $this->explore([$keyword], [self::RELATED_TOPICS])[self::RELATED_TOPICS][$keyword] ?? [];
    }

    /**
     * @param string|array<string> $keywords
     * @param string               $resolution COUNTRY (worldwide geo only), REGION or CITY
     * @return array<mixed> Google's raw GEO_MAP widget data (geoMapData)
     */
    public function getComparedGeo(string|array $keywords, string $resolution = 'CITY'): array
    {
        return $this->explore((array) $keywords, [self::GEO_MAP], $resolution)[self::GEO_MAP] ?? [];
    }

    /**
     * Run an explore query and fetch the requested widgets in one go.
     *
     * @param array<string> $keywords   Up to 5 keywords
     * @param array<string> $widgets    Subset of WIDGETS
     * @param string        $resolution GEO_MAP resolution: COUNTRY, REGION or CITY
     * @return array<string, array<mixed>> TIMESERIES and GEO_MAP hold the comparison data directly,
     *                                     RELATED_QUERIES and RELATED_TOPICS hold one entry per keyword.
     *                                     Google only returns RELATED_TOPICS for single-keyword queries.
     */
    public function explore(array $keywords, array $widgets = self::WIDGETS, string $resolution = 'CITY'): array
    {
        $keywords = array_values($keywords);
        $this->assertExploreArguments($keywords, $widgets);

        $req = [
            'comparisonItem' => array_map(
                fn (string $keyword) => ['keyword' => $keyword, 'geo' => $this->geo, 'time' => $this->time],
                $keywords
            ),
            'category' => $this->category,
            'property' => '',
        ];
        $response = $this->decode(
            $this->request(
                self::EXPLORE_ENDPOINT,
                ['hl' => $this->hl, 'tz' => $this->tz, 'req' => json_encode($req, JSON_THROW_ON_ERROR)]
            ),
            self::EXPLORE_ENDPOINT
        );

        $results = [];
        $widgetList = $response['widgets'] ?? null;
        foreach (is_array($widgetList) ? $widgetList : [] as $widget) {
            $selected = is_array($widget) ? $this->selectWidget($widget, $widgets) : null;
            if ($selected === null) {
                continue;
            }
            [$type, $index] = $selected;

            if ($type === self::GEO_MAP) {
                $widget['request']['resolution'] = $resolution;
                $widget['request']['includeLowSearchVolumeGeos'] = false;
            }
            $data = $this->fetchWidget(self::WIDGET_ENDPOINTS[$type], $widget);

            if ($index === null) {
                $results[$type] = $data;
            } else {
                $results[$type][$keywords[$index]] = $data;
            }
        }
        return $results;
    }

    /**
     * @param array<string> $keywords
     * @param array<string> $widgets
     */
    private function assertExploreArguments(array $keywords, array $widgets): void
    {
        if (!$keywords || count($keywords) > self::MAX_KEYWORDS) {
            throw new InvalidArgumentException('Provide between 1 and ' . self::MAX_KEYWORDS . ' keywords');
        }
        foreach ($widgets as $widget) {
            if (!in_array($widget, self::WIDGETS, true)) {
                throw new InvalidArgumentException("Unknown widget '$widget', see GTrends::WIDGETS");
            }
        }
    }

    /**
     * Decide whether an explore widget is wanted and how to file its data.
     *
     * Widget ids are TIMESERIES, GEO_MAP, RELATED_QUERIES, RELATED_TOPICS, optionally suffixed with
     * the keyword index (RELATED_QUERIES_1). TIMESERIES and GEO_MAP compare all keywords at once;
     * the per-keyword GEO_MAP_n Google also emits is dropped in favour of the comparison map.
     *
     * @param array<mixed>  $widget
     * @param array<string> $wanted
     * @return array{string, ?int}|null [type, keyword index or null for comparison widgets]
     */
    private function selectWidget(array $widget, array $wanted): ?array
    {
        $id = $widget['id'] ?? null;
        if (!isset($widget['token']) || !is_string($id) || !preg_match('/^([A-Z_]+?)(?:_(\d+))?$/', $id, $m)) {
            return null;
        }
        $type = $m[1];
        $perKeyword = $type === self::RELATED_QUERIES || $type === self::RELATED_TOPICS;
        if (!in_array($type, $wanted, true) || (!$perKeyword && isset($m[2]))) {
            return null;
        }
        return [$type, $perKeyword ? (int) ($m[2] ?? 0) : null];
    }

    /**
     * @param array<mixed> $widget
     * @return array<mixed>
     */
    private function fetchWidget(string $endpoint, array $widget): array
    {
        $query = [
            'hl'    => $this->hl,
            'tz'    => $this->tz,
            // Google rejects an empty geo encoded as [] — it must be {}
            'req'   => str_replace('"geo":[]', '"geo":{}', json_encode($widget['request'] ?? [], JSON_THROW_ON_ERROR)),
            'token' => self::str($widget['token'] ?? null),
        ];
        $data = $this->decode($this->request($endpoint, $query), $endpoint)['default'] ?? null;
        return is_array($data) ? $data : [];
    }

    private static function topicName(int $id): string
    {
        return self::TRENDING_TOPICS[$id] ?? (string) $id;
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private static function int(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    /** @return list<string> */
    private static function stringList(mixed $value): array
    {
        return is_array($value) ? array_values(array_map(self::str(...), $value)) : [];
    }

    /** @return list<int> */
    private static function intList(mixed $value): array
    {
        return is_array($value) ? array_values(array_map(self::int(...), $value)) : [];
    }

    /**
     * Google prefixes its JSON with the anti-hijack guard ")]}'" + newline; strip it and decode.
     *
     * @return array<mixed>
     */
    private function decode(string $body, string $uri): array
    {
        $decoded = json_decode(trim(substr($body, 5)), true);
        if (!is_array($decoded)) {
            throw GTrendsException::unexpectedResponse($uri, 'body is not JSON');
        }
        return $decoded;
    }

    /**
     * @param array<string, scalar> $query       GET parameters
     * @param array<string, scalar> $form        POST form fields; when non-empty the request is sent as POST
     * @param bool                  $withCookies Some trends/api endpoints answer 429 to a cookie-less request;
     *                                           when that happens the request is repeated with the cookies
     *                                           Google set on the first answer
     */
    private function request(string $uri, array $query, array $form = [], bool $withCookies = true): string
    {
        $cookieJar = tempnam(sys_get_temp_dir(), 'gtrends');
        $client = new Http\Client($uri, [
            'adapter'      => Http\Client\Adapter\Curl::class,
            'curloptions'  => [CURLOPT_COOKIEJAR => $cookieJar],
            'maxredirects' => 10,
            'timeout'      => 100,
        ]);
        if ($this->proxy) {
            $client->setOptions($this->proxy);
        }
        $client->setMethod($form ? 'POST' : 'GET');
        $client->setParameterGet($query);
        if ($form) {
            $client->setParameterPost($form);
        }

        try {
            $response = $client->send();
            if ($withCookies && $response->getStatusCode() === 429) {
                $client->setOptions(['curloptions' => [CURLOPT_COOKIEFILE => $cookieJar]]);
                $response = $client->send();
            }
        } finally {
            if (is_file($cookieJar)) {
                unlink($cookieJar);
            }
        }

        if ($response->getStatusCode() !== 200) {
            throw GTrendsException::requestFailed($uri, $response->getStatusCode());
        }
        return $response->getBody();
    }
}
