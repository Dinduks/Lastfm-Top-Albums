<?php
if( $_GET ){
    // TODO: remoeve limit and replace it with cols and rows size

    // we set url params
    $apiUrl = "http://ws.audioscrobbler.com/2.0/";
    $apiKey = "61d580c50e6e5e3f14b6bd9527e5395f";
    $method = "user.gettopalbums";
    ( $_GET["user"] ) ? $user = $_GET["user"] : $user = "dinduks";
    ( $_GET["period"] ) ? $period = $_GET["period"] : $period = "overall";
    ( $_GET["limit"] ) ? $limit = $_GET["limit"] : $limit = "20";

    // we create url
    $query = $apiUrl."?method=".$method."&user=".$user."&period=".$period.
             "&limit=".$limit."&api_key=".$apiKey;

    // create a DOMDocument which will contain the xml document returned by Last.fm API
    $topAlbums = new DOMDocument();
    $topAlbums->load($query);

    // we get the images urls
    $imagesUrlsList = array();
    $topAlbumsList = $topAlbums->getElementsByTagName("album");
    foreach( $topAlbumsList as $album ){
        $imagesUrlsList[] = $album->getElementsByTagName("image")->item(3)->nodeValue;
    }
    unset($album);

    // we create the images from the urls :)
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
    $PatchworkRows = 5;
    $PatchworkCols = 3;
    $PatchworkWidth = 299; // 299 is the max size of the Last.fm profile left column ;)
    $imagesSideSize = ($PatchworkWidth - ($PatchworkCols-1)) / $PatchworkCols; // we take out 1px for the border between the images
    $PatchworkHeight = $imagesSideSize * $PatchworkRows + ( $PatchworkRows - 1 );

    // we create our "empty" patchwork
    $patchwork = imagecreatetruecolor($PatchworkWidth, $PatchworkHeight);
    // we create white color (reminds me of SDL ^^)
    $white = imagecolorallocate($patchwork, 255, 255, 255);
    // we fill our patchwork by white color
    imagefilltoborder($patchwork, 0, 0, $white, $white);

    // now we "parse" our images in the patchwork, while resizing them :]
    for( $i=0; $i<$PatchworkRows; $i++ )
        for( $j=0; $j<$PatchworkCols; $j++ )
            imagecopyresized($patchwork, $images[$PatchworkCols*$i+$j], $j*$imagesSideSize+$j, $i*$imagesSideSize+$i, 0, 0, $imagesSideSize, $imagesSideSize, imagesx($images[$PatchworkCols*$i+$j]), imagesy($images[$PatchworkCols*$i+$j]));

    header("Content-type: image/jpg");
    imagejpeg($patchwork);
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Last.fm top albums patchwork</title>
    </head>
    <body>
        <header></header>
        <div id="content">
            <form action="index.php" method="get">
                <p>
                    <label for="user">Last.fm username</label>
                    <input type="text" name="user" id="user" />
                </p>
                <p>
                    <label for="period">Period</label>
                    <select name="period" id="period">
                        <option value="overall">Overall</option>
                        <option value="7day">7 days</option>
                        <option value="3month">3 months</option>
                        <option value="6month">6 months</option>
                        <option value="12month">One year</option>
                    </select>
                </p>
                <p>
                    <label>Limit</label>
                    <select name="limit" id="limit">
                        <option value="3">3</option>
                        <option value="6">6</option>
                        <option value="9">9</option>
                        <option value="12">12</option>
                        <option value="15">15</option>
                        <option value="18">18</option>
                        <option value="21">21</option>
                        <option value="24">24</option>
                        <option value="27">27</option>
                        <option value="30">30</option>
                    </select>
                </p>
                <p>
                    <input type="submit" value="Generate!" />
                </p>
            </form>
        </div>
        <footer></footer>
    </body>
</html>