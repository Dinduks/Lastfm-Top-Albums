<?php

if(!isset($_GET) || empty($_GET)){
    header('Location: index.php');
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
// Get 5 more albums incase there isn't an available
// image for one of the requested albums #lazyhackftw
$limit      = ($cols * $rows) + 5;

// create the url
$query = "$apiUrl?method=$method&user=$user&period=$period&limit=$limit&api_key=$apiKey";

// check if the image isn't already loaded
$response     = file_get_contents($query);
$responseHash = md5($response);


$fileName = "images/$user.$period.$rows.$cols.$imagesSize.$responseHash";
if (file_exists($fileName)) {
    header("Content-type: image/jpg");
    echo file_get_contents($fileName);
    exit;
}

// create a DOMDocument which will contain the xml document returned by Last.fm's Web service
$topAlbums = new DOMDocument();
$topAlbums->load($query);

// get the images' urls
$imagesUrlsList = array();
$topAlbumsList = $topAlbums->getElementsByTagName("album");
for ($i=0; $i<$limit; $i++) {
    if (!preg_match('/default_album/', $topAlbumsList->item($i)->getElementsByTagName("image")->item(3)->nodeValue))
        $imagesUrlsList[] = $topAlbumsList->item($i)->getElementsByTagName("image")->item(3)->nodeValue;
}

// create the images
$images = array();
foreach( $imagesUrlsList as $imageUrl ){
    $explodedImageUrl = explode(".", $imageUrl);
    $explodedImageUrlSize = sizeof($explodedImageUrl);
    $imageExtension = $explodedImageUrl[$explodedImageUrlSize-1];
    if( $imageExtension == "jpg" )
        $images[] = imagecreatefromjpeg($imageUrl);
    if( $imageExtension == "png" )
        $images[] = imagecreatefrompng($imageUrl);
    if($imageExtension == "gif")
        $images[] = imagecreatefromgif($imageUrl);
}
unset($imageUrl);

// srsbsns: create our albums patchwork \o/
(isset($imagesSize)) ? $imagesSideSize = $imagesSize : $imagesSideSize = 99;
$PatchworkWidth = $imagesSideSize * $cols + ($cols - 1); // 299 is the max size of the Last.fm profile left column ;)
$PatchworkHeight = $imagesSideSize * $rows + ($rows - 1);

// create the "empty" patchwork
$patchwork = imagecreatetruecolor($PatchworkWidth, $PatchworkHeight);
// create a white color (reminds me of SDL ^^)
$white = imagecolorallocate($patchwork, 255, 255, 255);
// we fill our patchwork by the white color
imagefilltoborder($patchwork, 0, 0, $white, $white);

// now we "parse" our images in the patchwork, while resizing them :]
for( $i=0; $i<$rows; $i++ ) {
    for( $j=0; $j<$cols; $j++ ) {
        imagecopyresampled($patchwork, $images[$cols*$i+$j], $j*$imagesSideSize+$j, $i*$imagesSideSize+$i, 0, 0, $imagesSideSize, $imagesSideSize, imagesx($images[$cols*$i+$j]), imagesy($images[$cols*$i+$j]));
    }
}

// save the image into a file
imagejpeg($patchwork, $fileName);

// display the image
header("Content-type: image/jpg");
imagejpeg($patchwork);
