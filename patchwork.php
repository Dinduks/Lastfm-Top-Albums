<?php

if (!isset($_GET) || empty($_GET)) {
    header('Location: index.php');
    exit;
}

function fail($status, $message) {
    http_response_code($status);
    header('Content-type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

// download covers in parallel and decode them; returns $key => GD image,
// leaving out any cover that failed to download or decode
function fetchCovers(array $urls, $concurrency = 16) {
    $covers = array();
    if (!$urls) return $covers;

    // no curl extension: same contract, one cover at a time
    if (!function_exists('curl_multi_init')) {
        $context = stream_context_create(array('http' => array('timeout' => 6)));
        foreach ($urls as $key => $url) {
            $data = @file_get_contents($url, false, $context);
            $image = $data === false ? false : @imagecreatefromstring($data);
            if ($image) $covers[$key] = $image;
        }
        return $covers;
    }

    $multi   = curl_multi_init();
    $pending = array_keys($urls);
    $running = array();   // handle id => array(handle, $key)

    // curl handles are resources before PHP 8 and objects since
    $id = function ($handle) {
        return is_object($handle) ? spl_object_id($handle) : (int)$handle;
    };
    $start = function () use ($multi, $urls, &$pending, &$running, $id) {
        $key = array_shift($pending);
        $handle = curl_init($urls[$key]);
        curl_setopt_array($handle, array(
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FOLLOWLOCATION  => true,
            // one connection per cover: over HTTP/2 every download shares a
            // single connection, and when it stalls all of them stall
            CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
            CURLOPT_CONNECTTIMEOUT  => 3,
            CURLOPT_TIMEOUT         => 6,
            // covers are ~36KB: give up on a stalled download after 2s
            CURLOPT_LOW_SPEED_LIMIT => 1024,
            CURLOPT_LOW_SPEED_TIME  => 2,
        ));
        curl_multi_add_handle($multi, $handle);
        $running[$id($handle)] = array($handle, $key);
    };

    while ($pending && count($running) < $concurrency) $start();

    while ($running) {
        curl_multi_exec($multi, $active);
        if (curl_multi_select($multi, 1.0) === -1) usleep(10000);

        while ($done = curl_multi_info_read($multi)) {
            $handle = $done["handle"];
            list(, $key) = $running[$id($handle)];
            unset($running[$id($handle)]);

            $data = curl_multi_getcontent($handle);
            $ok = $done["result"] === CURLE_OK
                && curl_getinfo($handle, CURLINFO_HTTP_CODE) === 200
                && $data !== "";
            $image = $ok ? @imagecreatefromstring($data) : false;
            if ($image) $covers[$key] = $image;

            curl_multi_remove_handle($multi, $handle);
            if ($pending) $start();
        }
    }

    curl_multi_close($multi);
    return $covers;
}

$apiUrl = "http://ws.audioscrobbler.com/2.0/";
$apiKey = "61d580c50e6e5e3f14b6bd9527e5395f";
$method = "user.gettopalbums";

$user       = $_GET["user"];
$period     = $_GET["period"];
// same limits as the form on index.php
$rows       = max(1, min(20, (int)$_GET["rows"]));
$cols       = max(1, min(4, (int)$_GET["cols"]));
// covers are fetched at 300px (Last.fm's largest listed size): don't upscale
$imagesSize = isset($_GET["imageSize"]) ? max(10, min(300, (int)$_GET["imageSize"])) : null;
$noborder   = (bool)(isset($_GET["noborder"]) && $_GET["noborder"]);
// Get 5 more albums incase there isn't an available
// image for one of the requested albums #lazyhackftw
$limit      = ($cols * $rows) + 5;

// create the url
$query = $apiUrl . "?" . http_build_query(array(
    "method"  => $method,
    "user"    => $user,
    "period"  => $period,
    "limit"   => $limit,
    "api_key" => $apiKey,
), "", "&");

// fetch the top albums; ignore_errors keeps the body of non-2xx responses,
// which carries Last.fm's error message (e.g. "User not found")
$context  = stream_context_create(array('http' => array('ignore_errors' => true)));
$response = @file_get_contents($query, false, $context);

// create a DOMDocument which will contain the information returned by Last.fm's Web service
$topAlbums = new DOMDocument();
if ($response === false || !@$topAlbums->loadXML($response)) {
    fail(502, "Could not reach Last.fm. Please try again later.");
}
if ($topAlbums->documentElement->getAttribute('status') !== 'ok') {
    $error = $topAlbums->getElementsByTagName('error')->item(0);
    // error 6: "User not found" / invalid parameters
    $status = ($error && $error->getAttribute('code') === '6') ? 404 : 502;
    fail($status, "Last.fm error: " . ($error ? trim($error->nodeValue) : "unknown error"));
}

// check if the image isn't already loaded
$responseHash = md5($response);

// the parts come straight from the query string: strip anything that could
// leave images/ (e.g. "../"); the response hash keeps distinct users apart
$fileNameParts = array();
foreach (array($user, $period, $rows, $cols, $imagesSize) as $part) {
    $fileNameParts[] = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$part);
}
$fileName = "images/" . implode(".", $fileNameParts) . ".$responseHash";
if (file_exists($fileName)) {
    header("Content-type: image/jpg");
    echo file_get_contents($fileName);
    exit;
}

// get the images' urls (300px "extralarge"), in ranking order, skipping
// albums without artwork
$imagesUrlsList = array();
$topAlbumsList = $topAlbums->getElementsByTagName("album");
for ($i=0; $i<min($limit, $topAlbumsList->length); $i++) {
    $imageNode = $topAlbumsList->item($i)->getElementsByTagName("image")->item(3);
    $imageUrl  = $imageNode ? trim($imageNode->nodeValue) : "";
    if ($imageUrl !== "" && !preg_match('/default_album/', $imageUrl))
        $imagesUrlsList[] = $imageUrl;
}

// download only as many covers as there are cells, then replace any that
// failed with the next albums in the ranking (the 5 spares)
$cells    = $rows * $cols;
$covers   = array();
$next     = 0;
// stay well inside PHP's 30s limit: no retries or spares past this point
$deadline = microtime(true) + 15;
while (count($covers) < $cells && $next < count($imagesUrlsList)
       && ($next === 0 || microtime(true) < $deadline)) {
    $batch = array_slice($imagesUrlsList, $next, $cells - count($covers), true);
    $next += count($batch);
    $fetched = fetchCovers($batch);
    // the image CDN intermittently 404s some covers (seen ~40% of requests
    // for one cover): retry twice, briefly spaced, before giving the cell
    // to a spare album
    for ($try = 0; $try < 2 && microtime(true) < $deadline && ($missed = array_diff_key($batch, $fetched)); $try++) {
        usleep(250000);
        $fetched += fetchCovers($missed);
    }
    $covers += $fetched;
}
ksort($covers);
$images = array_values($covers);

// srsbsns: create our albums patchwork \o/
(isset($imagesSize)) ? $imagesSideSize = $imagesSize : $imagesSideSize = 99;
$PatchworkWidth = $imagesSideSize * $cols + ($cols - 1); // 299 is the max size of the Last.fm profile left column ;)
$PatchworkHeight = $imagesSideSize * $rows + ($rows - 1);

// create the "empty" patchwork
$patchwork = imagecreatetruecolor($PatchworkWidth, $PatchworkHeight);
if (!$noborder) {
    // create a white color (reminds me of SDL ^^)
    $white = imagecolorallocate($patchwork, 255, 255, 255);
    // we fill our patchwork by the white color
    imagefilltoborder($patchwork, 0, 0, $white, $white);
}

// now we "parse" our images in the patchwork, while resizing them :]
for ($i=0; $i<$rows; $i++) {
    for ($j=0; $j<$cols; $j++) {
        // the user may have fewer albums with covers than requested cells
        if (!isset($images[$cols*$i+$j]) || !$images[$cols*$i+$j]) continue;
        imagecopyresampled($patchwork, $images[$cols*$i+$j], $j*$imagesSideSize+$j, $i*$imagesSideSize+$i, 0, 0, $imagesSideSize+intval($noborder), $imagesSideSize+intval($noborder), imagesx($images[$cols*$i+$j]), imagesy($images[$cols*$i+$j]));
    }
}

// save the image into a file
imagejpeg($patchwork, $fileName);

// display the image
header("Content-type: image/jpg");
imagejpeg($patchwork);
