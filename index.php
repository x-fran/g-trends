<?php

require 'vendor/autoload.php';

use Google\GTrends;
$options = [
    'hl' => 'en-US',
    'tz' => -60,
    'geo' => 'IE',
];
/** @var Google\GTrends $gt */
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

    <!--[if lt IE 9]>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script>
    <![endif]-->
</head>

<body>
<?php

print_r('<pre>');

print_r("\n\n <h1>GTrends getDailySearchTrends</h1>\n ");
print_r($gt->getDailySearchTrends());
print_r("\n\n");

print_r("\n\n <h1>GTrends getRealTimeSearchTrends</h1>\n ");
print_r($gt->getRealTimeSearchTrends());
print_r("\n\n");

print_r("\n\n <h1>GTrends getRelatedSearchQueries</h1>\n ");
print_r($gt->getRelatedSearchQueries(['Donald Trump']));
print_r("\n\n");

print_r("\n\n <h1>GTrends interestOverTime</h1>\n ");
print_r($gt->interestOverTime('Donald Trump'));
print_r("\n\n");

print_r("\n\n <h1>GTrends getRelatedTopics</h1>\n ");
print_r($gt->getRelatedTopics('Donald Trump'));
print_r("\n\n");

print_r("\n\n <h1>GTrends interestBySubregion</h1>\n ");
print_r($gt->interestBySubregion(['Dublin']));
print_r("\n\n");

print_r("\n\n <h1>GTrends interestByCity</h1>\n ");
print_r($gt->interestByCity(['Dublin']));
print_r("\n\n");

print_r("\n\n <h1>GTrends interestByMetro</h1>\n ");
print_r($gt->interestByMetro(['Dublin']));
print_r("\n\n");

print_r("\n\n <h1>GTrends suggestionsAutocomplete</h1>\n ");
print_r($gt->suggestionsAutocomplete('Donald Trump'));
print_r("\n\n");

print_r("\n\n <h1>GTrends suggestionsAutocomplete</h1>\n ");
print_r($gt->getCategories());
print_r("\n\n");

?>
<script src=""></script>
</body>
</html>
