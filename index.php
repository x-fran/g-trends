<?php

require 'vendor/autoload.php';

use Google\GTrends;

$gt = new GTrends();
?>
<!doctype html>

<html lang="en">
<head>
    <meta charset="utf-8">

    <title>GTrends</title>
    <meta name="description" content="">
    <meta name="author" content="">

    <link rel="stylesheet" href="">

    <!--[if lt IE 9]>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script>
    <![endif]-->
</head>

<body>
<?php

print_r('<pre>');
/*print_r("\n\n <h1>GTrends interestOverTime</h1>\n ");
print_r($gt->interestOverTime('Dublin'));
print_r("\n\n <h1>GTrends relatedQueries</h1>\n ");
print_r($gt->relatedQueries(['Dublin']));
print_r("\n\n <h1>GTrends trendingSearches</h1>\n ");
print_r($gt->trendingSearches('p54', date('Ymd')));
print_r("\n\n <h1>GTrends topCharts</h1>\n ");
print_r($gt->topCharts('201708', 'basketball_players'));
print_r("\n\n <h1>GTrends interestBySubregion</h1>\n ");
print_r($gt->interestBySubregion(['Dublin']));
print_r("\n\n <h1>GTrends suggestionsAutocomplete</h1>\n ");
print_r($gt->suggestionsAutocomplete('Dublin'));*/
print_r("\n\n <h1>GTrends latestStories</h1>\n ");
print_r($gt->latestStories());
print_r("\n\n");

?>
<script src=""></script>
</body>
</html>
