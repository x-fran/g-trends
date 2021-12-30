<?php

require 'vendor/autoload.php';

use XFran\GTrends\GTrends;

$options = [
    'hl'        => 'en-US',
    'tz'        => 0,
    'geo'       => 'US',
    'time'      => 'all',
    'category'  => 0,
];
$gt = new GTrends($options);

?>

<!doctype html>

<html lang="en">
    <head>
        <meta charset="utf-8">

        <title>GTrends</title>
        <meta name="description" content="">
        <meta name="author" content="">

        <link rel="stylesheet" href="">
    </head>

    <body>
        <?php

        print_r('<pre>');

        print_r("\n\n <h1>GTrends getRealTimeSearchTrends</h1>\n ");
        print_r($gt->getRealTimeSearchTrends());
        print_r("\n\n");

        print_r("\n\n <h1>GTrends getDailySearchTrends</h1>\n ");
        print_r($gt->getDailySearchTrends());
        print_r("\n\n");

        print_r("\n\n <h1>GTrends getSuggestionsAutocomplete</h1>\n ");
        print_r($gt->getSuggestionsAutocomplete('Donald Trump'));
        print_r("\n\n");

        print_r("\n\n <h1>GTrends getGeo</h1>\n ");
        print_r($gt->getGeo());
        print_r("\n\n");

        print_r("\n\n <h1>GTrends getCategories</h1>\n ");
        print_r($gt->getCategories());
        print_r("\n\n");

        print_r("\n\n <h1>GTrends getAllOneKeyWord</h1>\n ");
        print_r($gt->getAllOneKeyWord('Donald Trump'));
        print_r("\n\n");

        print_r("\n\n <h1>GTrends getAllMultipleKeyWords</h1>\n ");
        print_r($gt->getAllMultipleKeyWords(['Donald Trump', 'Barack Obama']));
        print_r("\n\n");

        ?>
        <script src=""></script>
    </body>
</html>
