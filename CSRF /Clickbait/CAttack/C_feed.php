<?php /* C_feed.php — Attack: victim-side view; risk badges intentionally hidden */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Victim Feed (Attack)</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  *{box-sizing:border-box}
  body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;margin:0}
  .wrap{padding:16px}
  .h{margin:0 0 12px 0;padding:10px 12px;border-bottom:1px solid #e5e7eb;background:#f3f4f6}
  .card{border-top:4px solid #f59e0b;background:#fff7ed;padding:12px 14px;border-radius:8px}
  .title{margin:0 0 6px 0;font-weight:800}
  .meta{color:#64748b;font-size:13px;margin-top:4px}
  .a{color:#2563eb;text-decoration:none;cursor:pointer}
  .a:hover{text-decoration:underline}
  .safe{display:inline-block;margin-left:10px;padding:2px 8px;border:1px solid #bae6fd;border-radius:999px;background:#ecfeff;color:#0369a1;font-size:12px}
</style>
</head>
<body>
  <div class="h">Victim feed (allow-list disabled here, for attack demo)</div>
  <div class="wrap">
    <h3>UCC Library hours update <span class="safe">safe news</span></h3>

    <div class="card" title="A sensational teaser that leads to an external bait page.">
      <p class="title">Shock! Students get exam “auto-pass” method!</p>
      <div class="meta">Hover to see why users may be tempted. Click to open on the right.</div>
      <p style="margin:6px 0 0 0;">
        <a id="read" class="a" href="#">Read now</a>
      </p>
    </div>

    <h3 style="margin-top:18px;">Clubs &amp; Societies Fair this Friday <span class="safe">safe news</span></h3>
  </div>

<script>
  // When the teaser is clicked, notify the parent panel to open the bait page in the right iframe.
  document.getElementById('read').addEventListener('click',(e)=>{
    e.preventDefault();
    try{ window.parent.postMessage({type:'OPEN_BAIT'}, '*'); }catch(_){}
  });
</script>
</body>
</html>
