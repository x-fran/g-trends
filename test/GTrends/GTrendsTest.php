<?php

namespace XFran\GTrends;

use PHPUnit\Framework\TestCase;

class GTrendsTest extends TestCase
{
    public GTrends $gt;

    public function setUp(): void
    {
        $this->gt = new GTrends();
    }

    public function testIfGetSuggestionsAutocompleteReturnsNotEmptyArray(): void
    {
        $suggestions = $this->gt->getSuggestionsAutocomplete('Donald Trump');
        $this->assertNotEmpty($suggestions);
    }

    public function testIfGetGeoReturnsNotEmptyArray(): void
    {
        $geo = $this->gt->getGeo();
        $this->assertNotEmpty($geo);
    }

    public function testIfGetRealTimeSearchTrendsReturnsNotEmptyArray(): void
    {
        $realTimeSearchTrends = $this->gt->getRealTimeSearchTrends();
        $this->assertNotEmpty($realTimeSearchTrends);
    }

    public function testIfGetCategoriesReturnsNotEmptyArray(): void
    {
        $categories = $this->gt->getCategories();
        $this->assertNotEmpty($categories);
    }

    public function testIfGetAllOneKeyWordReturnsNotEmptyArray(): void
    {
        $allOneKeyword = $this->gt->getAllOneKeyWord('Donald Trump');
        $this->assertNotEmpty($allOneKeyword);
    }

    public function testIfGetAllMultipleKeyWordsReturnsNotEmptyArray(): void
    {
        $allMultipleKeyWords = $this->gt->getAllMultipleKeyWords(['Donald Trump', 'Barack Obama']);
        $this->assertNotEmpty($allMultipleKeyWords);
    }
}
