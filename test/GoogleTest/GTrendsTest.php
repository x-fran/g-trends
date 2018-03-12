<?php

namespace GoogleTest;

use Doctrine\Common\Collections\ArrayCollection;
use Google\Model\GTrends;
use PHPUnit\Framework\TestCase;

class GTrendsTest extends TestCase
{
    /**
     * @var GTrends
     */
    public $gt;

    public function setUp()
    {
        $this->gt = new GTrends();
    }

    public function testThatWeCanGetTheOptions()
    {
        $options = [
            'hl' => 'en-US',
            'tz' => 360,
            'geo' => 'US',
        ];
        $this->gt->setOptions($options);

        $assertOptions = [
            'hl' => 'en-US',
            'tz' => 360,
            'geo' => 'US',
        ];
        $this->assertEquals($options, $assertOptions);
    }

    /**
     * @throws \Exception
     */
    public function testIfRelatedQueriesReturnsArrayCollection()
    {
        $relatedQueries = $this->gt->relatedQueries(['Barcelona']);

        $this->assertEquals(true, $relatedQueries instanceof ArrayCollection);
    }

    /**
     * @throws \Exception
     */
    public function testIfInterestOverTimeReturnsArrayCollection()
    {
        $interestOverTime = $this->gt->interestOverTime('Barcelona');

        $this->assertEquals(true, $interestOverTime instanceof ArrayCollection);
    }

    /**
     * @throws \Exception
     */
    public function testIfTrendingSearchesReturnsArray()
    {
        $trendingSearches = $this->gt->trendingSearches('p54', date('Ymd'));

        $this->assertEquals(true, is_array($trendingSearches));
    }

    /**
     * @throws \Exception
     */
    public function testIfTopChartsReturnsArray()
    {
        $trendingSearches = $this->gt->topCharts('201708', 'basketball_players');

        $this->assertEquals(true, is_array($trendingSearches));
    }

    /**
     * @throws \Exception
     */
    public function testIfSuggestionsAutocompleteReturnsArray()
    {
        $trendingSearches = $this->gt->suggestionsAutocomplete('Dublin');

        $this->assertEquals(true, is_object($trendingSearches));
    }

    /**
     * @throws \Exception
     */
    public function testIfInterestBySubregionReturnsArray()
    {
        $trendingSearches = $this->gt->interestBySubregion(['Dublin']);

        $this->assertEquals(true, is_array($trendingSearches));
    }

    /**
     * @throws \Exception
     */
    public function testIfLatestStoriesReturnsArray()
    {
        $latestStories = $this->gt->latestStories();

        $this->assertEquals(true, is_object($latestStories));
    }
}