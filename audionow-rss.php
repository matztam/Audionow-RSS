<?php

/*
Dependencies:
    php-curl

Usage:
    Simply pass the id of the show with the show parameter

    Show url: https://audionow.de/podcast/c94bf591-f82b-4d91-af2a-c283a14456f3
    Id: c94bf591-f82b-4d91-af2a-c283a14456f3

    Feed url: https://example.com/audionow-rss.php?show=c94bf591-f82b-4d91-af2a-c283a14456f3
*/

header('Content-Type: application/rss+xml; charset=utf-8');

$showId = $_GET['show'];

if(!preg_match('/^(\{)?[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}(?(1)\})$/i', $showId)){
	exit;
}

$show = getShow($showId);
$episodes = getEpisodes($showId);


print('<rss xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">');
print('<channel>');

printf('<title>%s</title>', escapeString($show->title));
printf('<link>%s</link>', $show->permalink);

print('<image>');
printf('<url>%s</url>', escapeString($show->imageInfo->optimizedImageUrls->{'768'}));
printf('<title>%s</title>', escapeString($show->title));
printf('<link>%s</link>', $show->permalink);
print('</image>');

printf('<description>%s</description>', escapeString($show->description));
printf('<atom:link href="%s" rel="self" type="application/rss+xml"/>', "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");

foreach ($episodes as $item) {
    print('<item>');
    printf('<title>%s</title>', escapeString($item->title));
    printf('<description>%s</description>', escapeString($item->description));
    printf('<guid>%s</guid>', escapeString($item->permalink));
    printf('<link>%s</link>', escapeString($item->mediaURL));
    printf('<enclosure url="%s" length="%d" type="audio/mpeg"/>', escapeString($item->mediaURL), $item->fileSize);
    printf('<media:content url="%s" medium="audio" duration="%d" type="audio/mpeg"/>', escapeString($item->mediaURL), $item->duration);
    printf('<pubDate>%s</pubDate>', (new DateTime($item->publicationDate))->format(DATE_RSS));
    printf('<itunes:duration>%d</itunes:duration>', $item->duration);
    print('</item>');
}


print('</channel>');
print('</rss>');


function getShow($showId) {
    $url = sprintf('https://api-v4.audionow.de/api/v4/media/%s.json', $showId);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $output = curl_exec($ch);

    $obj = json_decode($output);
    
    return $obj;
}

function getEpisodes($showId) {
	$page = 1;
	$totalPages = 1;
	$episodes = [];
	
	do {
		$url = sprintf('https://api-v4.audionow.de/api/v4/podcast/%s/episodes.json?page=%d', $showId, $page);
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


		$output = curl_exec($ch);

		$obj = json_decode($output);
		
		$episodes = array_merge($episodes, $obj->data);
		
		
		
		$totalPages = $obj->meta->pagination->total_pages;		

		$page++;
	} while($page <= $totalPages);
	
    return $episodes;
}


function escapeString($string) {
    return htmlspecialchars($string, ENT_XML1, 'UTF-8');
}

function getFileLength($url) {
    $headers = get_headers($url, 1);
    $filesize = $headers['Content-Length'];

    return $filesize;
}
