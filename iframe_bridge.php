<?php
/**
 * Iframe Bridge - Cross-Origin URL Tracker
 * 
 * This lightweight wrapper page solves the cross-origin iframe URL tracking problem.
 * When the main app (e.g. on Hostinger) embeds localhost content in an iframe,
 * the parent can't read the iframe's URL due to browser security.
 * 
 * This bridge page:
 * 1. Loads on localhost (same-origin as target content)
 * 2. Wraps the target URL in an inner iframe
 * 3. Since bridge + target = same-origin, it CAN read the inner iframe's URL
 * 4. Reports URL changes to the parent (Hostinger) via postMessage
 */

$targetUrl = isset($_GET['url']) ? $_GET['url'] : '';
if (!$targetUrl) {
    http_response_code(400);
    echo 'No URL specified. Usage: iframe_bridge.php?url=http://localhost/page';
    exit;
}
?><!DOCTYPE html>
<html style="margin:0;padding:0;height:100%;overflow:hidden">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bridge</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { width: 100%; height: 100%; overflow: hidden; }
    #bridgeFrame { width: 100%; height: 100%; border: none; display: block; }
</style>
</head>
<body>
<iframe id="bridgeFrame" src="<?= htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8') ?>"></iframe>
<script>
(function() {
    var inner = document.getElementById('bridgeFrame');
    var lastReportedUrl = '';
    var pollInterval = null;

    function getInnerUrl() {
        try {
            var loc = inner.contentWindow.location;
            if (loc && loc.href && loc.href !== 'about:blank') {
                return loc.href;
            }
        } catch(e) { /* inner iframe went cross-origin somehow */ }
        return '';
    }

    function reportUrl(url) {
        if (!url || url === lastReportedUrl) return;
        lastReportedUrl = url;
        try {
            // Send to the top-most parent (the Hostinger page)
            window.top.postMessage({ type: 'pm_iframe_nav', url: url }, '*');
        } catch(e) {}
    }

    // Report on inner iframe load events (catches link clicks, form submits, redirects)
    inner.addEventListener('load', function() {
        var url = getInnerUrl();
        if (url) reportUrl(url);
    });

    // Poll for SPA-style navigation (pushState, replaceState, hashchange)
    // that doesn't trigger a full page load
    pollInterval = setInterval(function() {
        var url = getInnerUrl();
        if (url) reportUrl(url);
    }, 800);

    // Report the initial URL once the bridge itself is ready
    window.addEventListener('load', function() {
        setTimeout(function() {
            var url = getInnerUrl();
            if (url) reportUrl(url);
        }, 100);
    });

    // Relay any postMessage from inner pages upward to top
    // (in case inner pages also have their own postMessage senders)
    window.addEventListener('message', function(e) {
        if (e.data && e.data.type === 'pm_iframe_nav' && e.source !== window.top) {
            try {
                window.top.postMessage(e.data, '*');
            } catch(ex) {}
        }
    });
})();
</script>
</body>
</html>
