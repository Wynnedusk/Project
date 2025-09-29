<?php
// If you want to actually send secure headers, include this at the very top
// (must be before any output):
// require_once __DIR__ . '/D_headers_demo.php';
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Campus Feed (Protected)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="D_rules.js"></script>
  <style>
    body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;margin:0}
    .head{margin:0;padding:10px 12px;background:#eef7ff;border-bottom:1px solid #dbeafe}
    .item{padding:12px 14px;border-bottom:1px solid #eee}
    .bait{background:#fff7ed;border-left:4px solid #fb923c}
    a{color:#0a58ca;text-decoration:none}
    .badge{display:inline-block;padding:2px 6px;background:#dcfce7;border:1px solid #86efac;border-radius:6px;margin-left:8px;font-size:12px}
  </style>
</head>
<body>
  <div class="head"><b>Victim feed (allow-list & interstitial)</b></div>

  <div class="item"><b>UCC Library hours update</b> – safe news</div>

  <div class="item bait">
    <b>Shock! Students get exam “auto-pass” method!</b>
    <div>
      <!-- Demonstration uses a non-allowlisted domain; 
           if you want to demo a trusted domain opening directly, 
           replace with e.g. https://www.bbc.com/news -->
      <a href="#" data-url="https://promo.example.net/win" class="x">Read now</a>
      <span class="badge">CSP/XFO active</span>
    </div>
  </div>

  <div class="item"><b>Clubs & Societies Fair this Friday</b> – safe news</div>

  <script>
    function sendToParent(payload){
      try{ parent.postMessage(payload, '*'); }
      catch(e){ console.error('postMessage failed', e); }
    }

    for(const a of document.querySelectorAll('a.x')){
      a.addEventListener('click', function(ev){
        ev.preventDefault();
        const url = this.getAttribute('data-url');

        // Fallback: if routing script did not load, always fall back to interstitial
        if(!window.ClickbaitDefense || !window.ClickbaitDefense.route){
          console.warn('D_rules.js not loaded, fallback to interstitial');
          sendToParent({type:'DEF_INTERSTITIAL', url});
          return;
        }

        // Route decision using allow-list logic
        const decision = window.ClickbaitDefense.route(url);
        console.log('route decision', decision);

        if(decision.kind === 'safe'){
          // Forward to parent: domain is allow-listed, open directly
          sendToParent({type:'DEF_OPEN_SAFE', url: decision.url});
        }else{
          // Forward to parent: not in allow-list, show interstitial first
          sendToParent({type:'DEF_INTERSTITIAL', url: decision.url});
        }
      });
    }
  </script>
</body>
</html>
