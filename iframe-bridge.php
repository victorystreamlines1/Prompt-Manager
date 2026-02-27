<?php
/**
 * iframe-bridge.php - Cross-origin localhost proxy for iframe navigation tracking
 *
 * When the main app runs on Hostinger and the iframe loads localhost content,
 * cross-origin restrictions prevent reading the iframe URL. This bridge:
 * 1. Fetches the localhost page content via cURL (server-side, same machine)
 * 2. Injects a postMessage script to notify the parent of the current URL
 * 3. Injects a link interceptor so all navigation stays within the bridge chain
 */

$targetUrl = $_GET['url'] ?? '';

if (!$targetUrl) {
    http_response_code(400);
    echo '<!DOCTYPE html><html><body><p>Missing <code>url</code> parameter.</p></body></html>';
    exit;
}

// Security: only allow localhost / 127.0.0.1 URLs
$parsed = parse_url($targetUrl);
$host = strtolower($parsed['host'] ?? '');
if (!in_array($host, ['localhost', '127.0.0.1'])) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><body><p>Only localhost URLs are allowed.</p></body></html>';
    exit;
}

// Auto-detect bridge path so links route back through this file
$bridgePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/iframe-bridge.php';

// ── Fetch the target content ──
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $targetUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml,*/*'],
]);
// Forward cookies for session continuity
if (!empty($_SERVER['HTTP_COOKIE'])) {
    curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);
}
$content    = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'text/html';
$httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($content === false) {
    http_response_code(502);
    echo '<!DOCTYPE html><html><body><p>Failed to fetch: ' . htmlspecialchars($targetUrl) . '</p></body></html>';
    exit;
}

http_response_code($httpCode);

// ── Non-HTML content: pass through directly ──
if (stripos($contentType, 'text/html') === false && stripos($contentType, 'application/xhtml') === false) {
    header('Content-Type: ' . $contentType);
    echo $content;
    exit;
}

// ── HTML content: inject base tag + postMessage bridge + link interceptor ──
$safeUrl    = htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8');
$jsUrl      = addslashes($targetUrl);
$jsBridge   = addslashes($bridgePath);

$injection = <<<INJECTION
<!-- PM iframe bridge -->
<base href="{$safeUrl}">
<script>
(function(){
    var realUrl = '{$jsUrl}';
    var bridgePath = '{$jsBridge}';
    // Notify parent of current URL
    if (window.parent !== window) {
        try { window.parent.postMessage({ type: 'pm_iframe_nav', url: realUrl }, '*'); } catch(e) {}
    }
    // Intercept all link clicks → route through bridge
    document.addEventListener('click', function(e) {
        var a = e.target.closest ? e.target.closest('a') : null;
        if (!a) return;
        var href = a.href;
        if (!href) return;
        if (href.indexOf('javascript:') === 0 || href === '#' || href.indexOf('#') === 0) return;
        try {
            var u = new URL(href);
            if (u.hostname !== 'localhost' && u.hostname !== '127.0.0.1') return;
            e.preventDefault();
            e.stopPropagation();
            window.location.href = bridgePath + '?url=' + encodeURIComponent(href);
        } catch(ex) {}
    }, true);
})();
</script>
INJECTION;

// Inject after <head> if present, otherwise prepend
if (preg_match('/<head[^>]*>/i', $content, $m, PREG_OFFSET_CAPTURE)) {
    $pos = $m[0][1] + strlen($m[0][0]);
    $content = substr($content, 0, $pos) . "\n" . $injection . "\n" . substr($content, $pos);
} else {
    $content = $injection . "\n" . $content;
}

header('Content-Type: text/html; charset=utf-8');
echo $content;
