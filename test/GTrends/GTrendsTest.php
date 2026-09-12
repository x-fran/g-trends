<?php

declare(strict_types=1);

namespace XFran\GTrends;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function str_contains;

/**
 * Most of these tests hit the live Google Trends endpoints. Google rate-limits the explore
 * endpoints per IP, so explore data is fetched once for the whole class and a 429 marks the
 * test skipped rather than failed.
 */
final class GTrendsTest extends TestCase
{
    /** @var array<string, array<mixed>>|null */
    private static ?array $explore = null;

    private GTrends $gt;

    protected function setUp(): void
    {
        $this->gt = new GTrends();
    }

    /** @return array<string, array<mixed>> */
    private function explore(): array
    {
        return self::$explore ??= $this->live(fn () => $this->gt->explore(['Dublin', 'Cork']));
    }

    /**
     * @template T
     * @param callable(): T $call
     * @return T
     */
    private function live(callable $call): mixed
    {
        try {
            return $call();
        } catch (GTrendsException $e) {
            if (str_contains($e->getMessage(), 'HTTP 429')) {
                self::markTestSkipped('Google rate-limited this IP: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    public function testGetSuggestionsReturnsTopics(): void
    {
        $topics = $this->live(fn () => $this->gt->getSuggestions('Donald Trump'));
        $this->assertNotEmpty($topics);
        $this->assertArrayHasKey('mid', $topics[0]);
        $this->assertArrayHasKey('title', $topics[0]);
    }

    public function testGetGeoReturnsNotEmptyArray(): void
    {
        $this->assertNotEmpty($this->live(fn () => $this->gt->getGeo()));
    }

    public function testGetCategoriesReturnsNotEmptyArray(): void
    {
        $this->assertNotEmpty($this->live(fn () => $this->gt->getCategories()));
    }

    public function testGetTrendingNowReturnsNormalisedTrends(): void
    {
        $trends = $this->live(fn () => $this->gt->getTrendingNow());
        $this->assertNotEmpty($trends);
        $first = $trends[0];
        $this->assertSame('US', $first['geo']);
        $this->assertNotSame('', $first['keyword']);
        $this->assertGreaterThan(0, $first['startedAt']);
        $this->assertGreaterThan(0, $first['searchVolume']);
        $this->assertSame(count($first['topics']), count($first['topicNames']));
    }

    public function testGetTrendingNowFiltersByTopic(): void
    {
        $trends = $this->live(fn () => $this->gt->getTrendingNow(24, 17));
        $this->assertNotEmpty($trends);
        foreach ($trends as $trend) {
            $this->assertContains(17, $trend['topics']);
        }
    }

    public function testGetTrendingNowRejectsUnsupportedWindow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->gt->getTrendingNow(12);
    }

    public function testGetTrendingNowRejectsUnknownTopic(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->gt->getTrendingNow(24, 999);
    }

    public function testGetTrendingNowRssReturnsItemsWithNews(): void
    {
        $items = $this->live(fn () => (new GTrends(geo: 'IE'))->getTrendingNowRss());
        $this->assertNotEmpty($items);
        $this->assertNotSame('', $items[0]['title']);
        $this->assertNotSame('', $items[0]['approxTraffic']);
        $this->assertArrayHasKey('newsItems', $items[0]);
    }

    public function testExploreReturnsTimeseriesComparison(): void
    {
        $timeseries = $this->explore()[GTrends::TIMESERIES];
        $this->assertNotEmpty($timeseries['timelineData']);
        $this->assertCount(2, $timeseries['timelineData'][0]['value']);
    }

    public function testExploreReturnsGeoMapComparison(): void
    {
        $geoMap = $this->explore()[GTrends::GEO_MAP];
        $this->assertNotEmpty($geoMap['geoMapData']);
        $this->assertCount(2, $geoMap['geoMapData'][0]['value']);
    }

    public function testExploreKeysRelatedQueriesByKeyword(): void
    {
        $related = $this->explore()[GTrends::RELATED_QUERIES];
        $this->assertSame(['Dublin', 'Cork'], array_keys($related));
        $this->assertArrayHasKey('rankedList', $related['Dublin']);
    }

    public function testExploreOmitsRelatedTopicsForMultipleKeywords(): void
    {
        // Google serves related topics for single-keyword queries only
        $this->assertArrayNotHasKey(GTrends::RELATED_TOPICS, $this->explore());
    }

    public function testGetComparedGeoHonoursResolution(): void
    {
        $regions = $this->live(fn () => $this->gt->getComparedGeo('Dublin', 'REGION'))['geoMapData'];
        $this->assertNotEmpty($regions);
        $this->assertNotSame(count($this->explore()[GTrends::GEO_MAP]['geoMapData']), count($regions));
    }

    /** @param list<string> $keywords */
    #[DataProvider('invalidKeywordLists')]
    public function testExploreRejectsInvalidKeywordCount(array $keywords): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->gt->explore($keywords);
    }

    /** @return iterable<string, array{list<string>}> */
    public static function invalidKeywordLists(): iterable
    {
        yield 'none' => [[]];
        yield 'six' => [['a', 'b', 'c', 'd', 'e', 'f']];
    }

    public function testExploreRejectsUnknownWidget(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->gt->explore(['Dublin'], ['NOPE']);
    }

    public function testRequestFailureThrows(): void
    {
        $this->expectException(GTrendsException::class);
        $this->expectExceptionMessage('failed with HTTP');
        (new GTrends(geo: 'XX'))->getInterestOverTime('Dublin');
    }
}
