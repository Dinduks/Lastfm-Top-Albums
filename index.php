<!DOCTYPE html>
<html>
    <head>
        <title>Last.fm top albums patchwork generator</title>
        <meta charset="utf-8" />
        <meta name="description" content="A tool that generates a patchwork, an image, based on the covers of your Last.fm top albums. It's simple, free, and it works." />
        <meta name="keywords" content="lastfm top albums generator, last.fm top albums generator, lastfm top albums, last.fm top albums, lastfm, last.fm, top albums" />
        <link href="main.css" rel="stylesheet" type="text/css" />
    </head>
    <body>
        <header>Last.fm top albums patchwork generator</header>
        <div id="content">
          <a href="https://github.com/Dinduks/Lastfm-Top-Albums" target="_blank"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png" alt="Fork me on GitHub"></a>
            <form action="patchwork.php" method="GET">
                <p>
                    <label for="user">Username</label>
                    <input type="text" name="user" id="user" value="Dinduks" autofocus />
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
                        <?php for ($i=1; $i<=20; $i++) : ?>
                        <option value="<?php echo $i; ?>" <?php echo ($i==5) ? "selected='selected'" : ""; ?>>
                            <?php echo $i; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </p>
                <p>
                    <label for="cols">Nr. of columns</label>
                    <select name="cols" id="cols">
                        <?php for ($i=1; $i<=20; $i++) : ?>
                        <option value="<?php echo $i; ?>" <?php echo ($i==2) ? "selected='selected'" : ""; ?>>
                            <?php echo $i; ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </p>
                <p>
                    <label for="imageSize">Images size</label>
                    <input type="text" value="150" name="imageSize" id="imagesSize" /> px
                </p>
                <p>
                    <input type="checkbox" name="noborder" id="noborder" /> px
                    <label for="noborder">Remove that ugly white border!</label>
                </p>
                <p>
                    <input type="submit" id="submit" value="Generate!" />
                </p>
            </form>
        </div>
        <footer></footer>
    </body>
</html>
