<?php

require 'vendor/autoload.php';

use XFran\GTrends\GTrends;

$gt = new GTrends(hl: 'en-US', tz: 0, geo: 'US', time: 'all', category: 0);

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

        $demos = [
            'getTrendingNow(4, GTrends::TRENDING_TOPICS Sports)' => fn () => $gt->getTrendingNow(4, 17),
            'getTrendingNow(24)' => fn () => $gt->getTrendingNow(24),
            'getTrendingNowRss' => fn () => $gt->getTrendingNowRss(),
            'getSuggestions' => fn () => $gt->getSuggestions('Donald Trump'),
            'getGeo' => fn () => $gt->getGeo(),
            'getCategories' => fn () => $gt->getCategories(),
            'explore (all widgets, one keyword)' => fn () => $gt->explore(['Donald Trump']),
            'explore (two keywords)' => fn () => $gt->explore(['Donald Trump', 'Barack Obama']),
            'getComparedGeo REGION' => fn () => $gt->getComparedGeo('Donald Trump', 'REGION'),
        ];

        foreach ($demos as $title => $demo) {
            print_r("\n\n <h1>GTrends $title</h1>\n ");
            try {
                print_r($demo());
            } catch (Throwable $e) {
                print_r(get_class($e) . ': ' . $e->getMessage());
            }
            print_r("\n\n");
        }

        ?>
        <script src=""></script>
    </body>
</html>
