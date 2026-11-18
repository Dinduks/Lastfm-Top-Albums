<?php
// http:// already 301s to https:// on the server; tell browsers to stay on
// https for a year. Ignored over plain http. Browsers that cached this
// refuse plain http until it expires, so keep the TLS certificate renewing.
header("Strict-Transport-Security: max-age=31536000");

$siteUrl     = "https://lastfmtopalbums.dinduks.com/";
$title       = "Last.fm Top Albums Patchwork has closed";
$description = "The Last.fm top albums collage generator has closed. From 1 December 2026, Last.fm API keys require a paid Last.fm Pro subscription.";
// link previews truncate around 125 characters, so they get a shorter one
$shareDescription = "The Last.fm top albums collage generator has closed.";
$siteName    = "Last.fm Top Albums Patchwork";
?><!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-9WY15PDQWC"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'G-9WY15PDQWC');
        </script>
        <title><?php echo $title; ?></title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="description" content="<?php echo $description; ?>" />
        <link rel="canonical" href="<?php echo $siteUrl; ?>" />

        <link rel="icon" href="favicon.svg" type="image/svg+xml" />
        <link rel="icon" href="favicon-48.png" type="image/png" sizes="48x48" />
        <link rel="apple-touch-icon" href="apple-touch-icon.png" />
        <meta name="theme-color" content="#0d0d0d" />

        <meta property="og:type" content="website" />
        <meta property="og:site_name" content="<?php echo $siteName; ?>" />
        <meta property="og:url" content="<?php echo $siteUrl; ?>" />
        <meta property="og:title" content="<?php echo $title; ?>" />
        <meta property="og:description" content="<?php echo $shareDescription; ?>" />
        <meta property="og:image" content="<?php echo $siteUrl; ?>og-image.png" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="og:image:alt" content="Top albums patchwork: a grid of album covers" />
        <meta name="twitter:card" content="summary_large_image" />

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
                <h1>Top<br />albums<br /><span class="accent">patchwork</span></h1>
                <p class="lede">Your most played Last.fm albums, stitched into one image.</p>
            </section>

            <section class="closed">
                <h2>This service has closed</h2>
                <p>From 1 December 2026, Last.fm only lets people with a paid Last.fm Pro subscription use its API, which this site needed to read your top albums. So the collage generator is no longer available, and existing collage links no longer show an image.</p>
                <p>Thanks to everyone who made a patchwork since 2011.</p>
                <ul class="links">
                    <li><a href="https://support.last.fm/t/api-keys-will-require-pro-from-1st-december/121030" target="_blank" rel="noopener">Last.fm's announcement</a></li>
                    <li><a href="https://github.com/Dinduks/Lastfm-Top-Albums" target="_blank" rel="noopener">The source code, to run it with your own API key</a></li>
                </ul>
            </section>
        </main>
    </body>
</html>
