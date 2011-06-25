<!DOCTYPE html>
<html>
    <head>
        <title>Last.fm top albums patchwork generator</title>
        <link href="main.css" rel="stylesheet" type="text/css" />
    </head>
    <body>
        <header>Last.fm top albums patchwork generator</header>
        <div id="content">
            <form action="patchwork.php" method="post">
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
                    <label for="rows">Nr. of rows</label>
                    <select name="rows" id="rows">
                        <?php for( $i=1; $i<=20; $i++ ){ ?>
                        <option value="<?php echo $i; ?>" <?php echo ($i==5) ? "selected=\"selected\"" : ""; ?>><?php echo $i; ?></option>
                        <?php } ?>
                    </select>
                </p>
                <p>
                    <label for="cols">Nr. of columns</label>
                    <select name="cols" id="cols">
                        <?php for( $i=1; $i<=20; $i++ ){ ?>
                        <option value="<?php echo $i; ?>" <?php echo ($i==3) ? "selected=\"selected\"" : ""; ?>><?php echo $i; ?></option>
                        <?php } ?>
                    </select>
                </p>
                <p>
                    <label for="imageSize">Image size</label>
                    <input type="text" value="99" name="imageSize" id="imageSize" /> px
                </p>
                <p>
                    <input type="submit" id="submit" value="Generate!" />
                </p>
            </form>
        </div>
        <footer></footer>
    </body>
</html>