<?php

if( $_POST ){
    
    $apiUrl = "http://ws.audioscrobbler.com/2.0/";
    $apiKey = "61d580c50e6e5e3f14b6bd9527e5395f";
    $method = "user.gettopalbums";

    $user = $_POST["user"];
    $period = $_POST["period"];
    $rows = $_POST["rows"];
    $cols = $_POST["cols"];

    $limit = $cols * $rows;

    // create url
    $query = $apiUrl."?method=".$method."&user=".$user."&period=".$period.
             "&limit=".$limit."&api_key=".$apiKey;

    // create a DOMDocument which will contain the xml document returned by Last.fm API
    $topAlbums = new DOMDocument();
    $topAlbums->load($query);

    // get the images urls
    $imagesUrlsList = array();
    $topAlbumsList = $topAlbums->getElementsByTagName("album");
    foreach( $topAlbumsList as $album ){
        $imagesUrlsList[] = $album->getElementsByTagName("image")->item(3)->nodeValue;
    }
    unset($album);

    // create the images from the urls
    $images = array();
    foreach( $imagesUrlsList as $imageUrl ){
        $explodedImageUrl = explode(".", $imageUrl);
        $explodedImageUrlSize = sizeof($explodedImageUrl);
        $imageExtension = $explodedImageUrl[$explodedImageUrlSize-1];
        if( $imageExtension == "jpg" )
            $images[] = imagecreatefromjpeg($imageUrl);
        if( $imageExtension == "png" )
            $images[] = imagecreatefrompng($imageUrl);
    }
    unset($imageUrl);

    // srsbsns: create our album patchwork \o/
    (isset($_POST["imageSize"])) ? $imagesSideSize = $_POST["imageSize"] : $imagesSideSize = 99;
    $PatchworkWidth = $imagesSideSize * $cols + ($cols - 1); // 299 is the max size of the Last.fm profile left column ;)
    $PatchworkHeight = $imagesSideSize * $rows + ($rows - 1);

    // create our "empty" patchwork
    $patchwork = imagecreatetruecolor($PatchworkWidth, $PatchworkHeight);
    // we create white color (reminds me of SDL ^^)
    $white = imagecolorallocate($patchwork, 255, 255, 255);
    // we fill our patchwork by white color
    imagefilltoborder($patchwork, 0, 0, $white, $white);

    // now we "parse" our images in the patchwork, while resizing them :]
    for( $i=0; $i<$rows; $i++ )
        for( $j=0; $j<$cols; $j++ )
            imagecopyresampled($patchwork, $images[$cols*$i+$j], $j*$imagesSideSize+$j, $i*$imagesSideSize+$i, 0, 0, $imagesSideSize, $imagesSideSize, imagesx($images[$cols*$i+$j]), imagesy($images[$cols*$i+$j]));

    header("Content-type: image/jpg");
    imagejpeg($patchwork);
}
?>