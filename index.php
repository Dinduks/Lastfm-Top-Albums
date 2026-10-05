<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Last.fm top albums patchwork generator</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="description" content="A tool that generates a patchwork, an image, based on the covers of your Last.fm top albums. It's simple, free, and it works." />
        <meta name="keywords" content="lastfm top albums generator, last.fm top albums generator, lastfm top albums, last.fm top albums, lastfm, last.fm, top albums" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Anton&display=swap" rel="stylesheet" />
        <!-- the server sends no Cache-Control, so version the URL by mtime to bust stale copies -->
        <link href="main.css?v=<?php echo filemtime(__DIR__ . '/main.css'); ?>" rel="stylesheet" type="text/css" />
    </head>
    <body>
        <header class="topbar">
            <span class="topbar-mark">Top albums</span>
            <a class="source" href="https://github.com/Dinduks/Lastfm-Top-Albums" target="_blank" rel="noopener">
                <svg viewBox="0 0 16 16" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
                <span>Source on GitHub</span>
            </a>
        </header>

        <main class="layout">
            <section class="intro">
                <h1>Top<br />albums<br />patchwork</h1>
                <p class="lede">Your most played Last.fm albums, stitched into one image.</p>
            </section>

            <form class="generator" action="patchwork.php" method="GET">
                <div class="field">
                    <label for="user">Last.fm username</label>
                    <input type="text" name="user" id="user" required autofocus
                           autocomplete="off" autocapitalize="off" spellcheck="false"
                           data-1p-ignore data-lpignore="true" placeholder="e.g. dinduks" />
                </div>

                <fieldset class="field">
                    <legend>Period</legend>
                    <div class="periods">
                        <label><input type="radio" name="period" value="7day" /><span>7 days</span></label>
                        <label><input type="radio" name="period" value="1month" /><span>1 month</span></label>
                        <label><input type="radio" name="period" value="3month" /><span>3 months</span></label>
                        <label><input type="radio" name="period" value="6month" /><span>6 months</span></label>
                        <label><input type="radio" name="period" value="12month" /><span>1 year</span></label>
                        <label><input type="radio" name="period" value="overall" checked /><span>All time</span></label>
                    </div>
                </fieldset>

                <div class="row">
                    <div class="field">
                        <label for="cols">Columns</label>
                        <input type="number" name="cols" id="cols" value="2" min="1" max="4" required />
                    </div>
                    <div class="field">
                        <label for="rows">Rows</label>
                        <input type="number" name="rows" id="rows" value="5" min="1" max="20" required />
                    </div>
                    <div class="field">
                        <label for="imageSize">Cover size</label>
                        <div class="suffixed">
                            <input type="number" name="imageSize" id="imageSize" value="150" min="10" max="500" required />
                            <span>px</span>
                        </div>
                    </div>
                </div>

                <label class="check">
                    <input type="checkbox" name="noborder" id="noborder" />
                    <span>No border between covers</span>
                </label>

                <figure class="preview">
                    <div class="mosaic" id="mosaic" aria-hidden="true"></div>
                    <figcaption id="dimensions" aria-live="polite"></figcaption>
                </figure>

                <button type="submit" id="submit">Generate</button>
                <p class="status" id="status" role="status" aria-live="polite"></p>
            </form>
        </main>

        <script>
            (function () {
                var form = document.querySelector(".generator");
                var mosaic = document.getElementById("mosaic");
                var dimensions = document.getElementById("dimensions");
                // flat "sleeve" colours standing in for album covers
                var sleeves = ["#111111", "#d51007", "#e8c547", "#2b59c3", "#9aa5b1", "#f08a4b", "#1f6f50", "#d9c5b2", "#5b3a6e", "#f4f1ea"];

                // read a number field, kept within its own min/max attributes
                function bounded(input) {
                    // valueAsNumber reads "1e1" as 10 (parseInt stops at the "e");
                    // truncate like patchwork.php's (int) cast
                    var n = Math.trunc(input.valueAsNumber);
                    if (isNaN(n)) n = parseInt(input.defaultValue, 10);
                    return Math.min(+input.max, Math.max(+input.min, n));
                }

                function render() {
                    var rows = bounded(form.rows);
                    var cols = bounded(form.cols);
                    var size = bounded(form.imageSize);
                    var border = !form.noborder.checked;

                    var cell = Math.max(4, Math.floor(Math.min(240 / cols, 240 / rows)));
                    mosaic.style.gridTemplateColumns = "repeat(" + cols + ", " + cell + "px)";
                    mosaic.style.gap = border ? "2px" : "0";

                    var html = "";
                    for (var i = 0; i < rows * cols; i++) {
                        html += '<i style="width:' + cell + 'px;height:' + cell + 'px;background:' + sleeves[(i * 7 + 3) % sleeves.length] + ';animation-delay:' + (i * 60 % 1200) + 'ms"></i>';
                    }
                    mosaic.innerHTML = html;

                    // same maths as patchwork.php, which always keeps a 1px
                    // gap between covers ("no border" only paints over it)
                    var width = size * cols + (cols - 1);
                    var height = size * rows + (rows - 1);
                    dimensions.textContent = cols + " × " + rows + " covers · " + width + " × " + height + " px";
                }

                form.addEventListener("input", render);
                // on leaving a field, snap an out-of-range value to the limit
                form.addEventListener("change", function (event) {
                    var input = event.target;
                    if (input.type !== "number") return;
                    input.value = bounded(input);
                    render();
                });

                // the image can take a while (one download per cover):
                // show that something is happening and block double submits
                var submit = document.getElementById("submit");
                var status = document.getElementById("status");
                form.addEventListener("submit", function () {
                    form.classList.add("loading");
                    form.setAttribute("aria-busy", "true");
                    submit.disabled = true;
                    submit.textContent = "Generating\u2026";
                    status.textContent = "Fetching covers from Last.fm. Bigger grids take longer.";
                });
                // coming back with the Back button restores the page from cache as-is
                window.addEventListener("pageshow", function () {
                    form.classList.remove("loading");
                    form.removeAttribute("aria-busy");
                    submit.disabled = false;
                    submit.textContent = "Generate";
                    status.textContent = "";
                });

                render();
            })();
        </script>
    </body>
</html>
