<?php
if( $_GET ){
    // we set url params
    $apiUrl = "http://ws.audioscrobbler.com/2.0/";
    $apiKey = "61d580c50e6e5e3f14b6bd9527e5395f";
    $method = "user.gettopalbums";
    
    $user = $_GET["user"];
    $period = $_GET["period"];
    $rows = $_GET["rows"];
    $cols = $_GET["cols"];
    
    $limit = $cols * $rows;

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
//    $imagesSideSize = ($PatchworkWidth - ($cols-1)) / $cols; // we take out 1px for the border between the images
    (isset($_GET["imageSize"])) ? $imagesSideSize = $_GET["imageSize"] : $imagesSideSize = 99;
    $PatchworkWidth = $imagesSideSize * $cols + ($cols - 1); // 299 is the max size of the Last.fm profile left column ;)
    $PatchworkHeight = $imagesSideSize * $rows + ($rows - 1);
    
    // we create our "empty" patchwork
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

<!DOCTYPE html>
<html>
    <head>
        <title>Last.fm top albums patchwork</title>
        <link href="main.css" rel="stylesheet" type="text/css" />
    </head>
    <body>
        <header>Last.fm album patchwork generator</header>
        <div id="content">
            <form action="index.php" method="get">
                <p>
                    <label for="user">Username</label>
                    <input type="text" name="user" id="user" value="Dinduks" />
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
                    <label for="rows">Rows</label>
                    <select name="rows" id="rows">
                        <?php for( $i=1; $i<=20; $i++ ){ ?>
                        <option value="<?php echo $i; ?>" <?php echo ($i==5) ? "selected=\"selected\"" : ""; ?>><?php echo $i; ?></option>
                        <?php } ?>
                    </select>
                </p>
                <p>
                    <label for="cols">Columns</label>
                    <select name="cols" id="cols">
                        <?php for( $i=1; $i<=20; $i++ ){ ?>
                        <option value="<?php echo $i; ?>" <?php echo ($i==3) ? "selected=\"selected\"" : ""; ?>><?php echo $i; ?></option>
                        <?php } ?>
                    </select>
                </p>
                <p>
                    <label for="imageSize">Image size</label>
                    <input type="text" value="99" name="imageSize" id="imageSize" />
                </p>
                <p>
                    <input type="submit" id="submit" value="Generate!" />
                </p>
            </form>
        </div>
        <footer></footer>
    </body>
</html>