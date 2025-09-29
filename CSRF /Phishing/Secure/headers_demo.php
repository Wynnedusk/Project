<?php
// Teaching-only: Send common security headers (still iframe-able within same origin)
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: default-src 'self'; frame-ancestors 'self'; img-src 'self' data:; script-src 'self' 'unsafe-inline'");
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Content-Type-Options: nosniff');
// HSTS makes sense only under HTTPS, but we include it here for demonstration:
if (!headers_sent()) {
  header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Secure Headers — Demo</title>
<style>
  body{
    font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
    background:#fff; margin:0; padding:20px
  }
  h1{margin:0 0 12px 0}
  table{border-collapse:collapse; width:100%; max-width:900px}
  th,td{border:1px solid #e5e7eb; padding:8px; text-align:left}
  th{background:#f3f4f6}
  code{background:#f3f4f6; padding:2px 6px; border-radius:6px}
</style>
</head>
<body>
  <h1>Secure Response Headers (Teaching)</h1>
  <table>
    <tr><th>Header</th><th>Value</th><th>Purpose</th></tr>
    <tr>
      <td><code>X-Frame-Options</code></td>
      <td><code>SAMEORIGIN</code></td>
      <td>Mitigates clickjacking while still allowing same-origin iframes (needed for this demo).</td>
    </tr>
    <tr>
      <td><code>Content-Security-Policy</code></td>
      <td><code>frame-ancestors 'self'</code></td>
      <td>Restricts which origins can embed this page in an iframe.</td>
    </tr>
    <tr>
      <td><code>Strict-Transport-Security</code></td>
      <td><code>max-age=31536000; includeSubDomains</code></td>
      <td>Forces HTTPS connections (effective only when served via HTTPS).</td>
    </tr>
    <tr>
      <td><code>Referrer-Policy</code></td>
      <td><code>strict-origin-when-cross-origin</code></td>
      <td>Limits referrer information leakage across sites.</td>
    </tr>
    <tr>
      <td><code>X-Content-Type-Options</code></td>
      <td><code>nosniff</code></td>
      <td>Prevents MIME-type sniffing by browsers.</td>
    </tr>
  </table>
  <p>This page is meant to be opened inside the demo panel to explain why security headers matter.</p>
</body>
</html>
