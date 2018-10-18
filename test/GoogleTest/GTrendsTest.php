<?php

namespace GoogleTest;

use Google\GTrends;
use PHPUnit\Framework\TestCase;

class GTrendsTest extends TestCase
{
    /* @var $gt GTrends */
    public $gt;

    public function setUp()
    {
        $this->gt = new GTrends();
    }

    public function testThatWeCanGetTheOptions()
    {
        /* @var $gt GTrends */
        $gt = $this->gt;

        $options = [
            'hl' => 'en-US',
            'tz' => 360,
            'geo' => 'US',
        ];
        $gt->setOptions($options);

        $assertOptions = [
            'hl' => 'en-US',
            'tz' => 360,
            'geo' => 'US',
        ];
        $this->assertEquals($options, $assertOptions);
    }

    public function testIfOptionsHasValidNumberOfKeys()
    {
        /* @var $gt GTrends */
        $gt = $this->gt;

        $this->expectExceptionMessage('Invalid number of options provided');

        $options = [
            0 => 'US',
            'hll' => 'en-US',
            'tz' => 360,
            'geo' => 'US',
        ];
        $gt->setOptions($options);
    }

    public function testIfOptionsHasValidKeys()
    {
        /* @var $gt GTrends */
        $gt = $this->gt;

        $this->expectExceptionMessage('Invalid keys provided');

        $options = [
            'hll' => 'en-US',
            'tz' => 360,
            'geo' => 'US',
        ];
        $gt->setOptions($options);
    }

    public function testIfOptionsHasValidValues()
    {
        /* @var $gt GTrends */
        $gt = $this->gt;

        $this->expectExceptionMessage('Invalid type values provided');

        $options = [
            'hl' => 'en-US',
            'tz' => 'sd',
            'geo' => 6546,
        ];
        $gt->setOptions($options);
    }

    public function testIfRelatedQueriesReturnsArray()
    {
        /* @var $gt GTrends */
        $gt = $this->gt;

        $relatedQueries = $gt->relatedQueries(['Barcelona']);

        $this->assertEquals(is_array($relatedQueries), true);
    }

    public function testIfRelatedTopicsReturnsArray()
    {
        /* @var $gt GTrends */
        $gt = $this->gt;

        $relatedTopics = $gt->relatedTopics('Restaurants');

        $this->assertEquals(is_array($relatedTopics), true);
    }

    public function testIfInterestOverTimeReturnsArray()
    {
        /* @var $gt GTrends */
        $gt = $this->gt;

        $interestOverTime = $gt->interestOverTime('Barcelona');

        $this->assertEquals(is_array($interestOverTime), true);
    }

    public function testIfTrendingSearchesReturnsArray()
    {
        /* @var $gt GTrends */
        $gt = $this->gt;

        $trendingSearches = $gt->trendingSearches('p54', date('Ymd'));

        $this->assertEquals(is_array($trendingSearches), true);
    }

    public function testIfTrendingSearchesRealtimeReturnsArray()
    {
        /* @var $gt GTrends */
        $gt = $this->gt;

        $trendingSearchesRealtime = $gt->trendingSearchesRealtime();

        $this->assertEquals(is_array($trendingSearchesRealtime), true);
    }

    public function testIfTopChartsReturnsArray()
    {
        /* @var $gt GTrends */
        $gt = $this->gt;

        $trendingSearches = $gt->topCharts('201708', 'basketball_players');

        $this->assertEquals(is_array($trendingSearches), true);
    }

    public function testIfTopChartsCategoriesReturnsArray()
    {
        /* @var $gt GTrends */
        $gt = $this->gt;

        $topChartsCategories = $gt->topChartsCategories('2017');

        $this->assertEquals(is_array($topChartsCategories), true);
    }

    public function testIfSuggestionsAutocompleteReturnsArray()
    {
        /* @var $gt GTrends */
        $gt = $this->gt;

        $trendingSearches = $gt->suggestionsAutocomplete('Dublin');

        $this->assertEquals(is_array($trendingSearches), true);
    }

    public function testIfInterestBySubregionReturnsArray()
    {
        /* @var $gt GTrends */
        $gt = $this->gt;

        $trendingSearches = $gt->interestBySubregion(['Dublin']);

        $this->assertEquals(is_array($trendingSearches), true);
    }

    public function testIfCategoriesReturnsArray()
    {
        /* @var $gt GTrends */
        $gt = $this->gt;

        $trendingSearches = $gt->categories();

        $this->assertEquals(is_array($trendingSearches), true);
    }
}
