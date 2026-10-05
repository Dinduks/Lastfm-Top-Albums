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

$apiUrl = "http://ws.audioscrobbler.com/2.0/";
$apiKey = "61d580c50e6e5e3f14b6bd9527e5395f";
$method = "user.gettopalbums";

$user       = $_GET["user"];
$period     = $_GET["period"];
$rows       = $_GET["rows"];
$cols       = $_GET["cols"];
$imagesSize = $_GET["imageSize"];
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

// get the images' urls
$imagesUrlsList = array();
$topAlbumsList = $topAlbums->getElementsByTagName("album");
for ($i=0; $i<min($limit, $topAlbumsList->length); $i++) {
    if (!preg_match('/default_album/', $topAlbumsList->item($i)->getElementsByTagName("image")->item(3)->nodeValue))
        $imagesUrlsList[] = $topAlbumsList->item($i)->getElementsByTagName("image")->item(3)->nodeValue;
}

// create the images
$images = array();
foreach($imagesUrlsList as $imageUrl){
    $explodedImageUrl = explode(".", $imageUrl);
    $explodedImageUrlSize = sizeof($explodedImageUrl);
    $imageExtension = $explodedImageUrl[$explodedImageUrlSize-1];
    switch ($imageExtension) {
      case 'jpg':
        $images[] = imagecreatefromjpeg($imageUrl);
        break;
      case 'png':
        $images[] = imagecreatefrompng($imageUrl);
        break;
      case 'gif':
        $images[] = imagecreatefromgif($imageUrl);
        break;
    }
}
unset($imageUrl);

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
