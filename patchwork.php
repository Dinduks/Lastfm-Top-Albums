<?php

// The service closed: from 1 December 2026 Last.fm API keys require a paid
// Last.fm Pro subscription. Existing collage links (forum signatures, blogs)
// get a clear "gone" instead of a Last.fm error. The generator is in git
// history (before the "close the service" commit).

if (!isset($_GET) || empty($_GET)) {
    header('Location: index.php');
    exit;
}

http_response_code(410);
header('Content-type: text/plain; charset=utf-8');
echo "Last.fm Top Albums Patchwork has closed. More at https://lastfmtopalbums.dinduks.com/";
