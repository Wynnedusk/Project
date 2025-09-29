// Clickbait defense router: allow-list & routing decision
(function () {
  // List of domains allowed to open directly (can be extended as needed)
  const ALLOW = ['ucc.ie', 'bbc.com', 'rte.ie', 'rté.ie'];

  /**
   * Extract the hostname from a given URL.
   * - Strips "www." prefix for consistency.
   * - Falls back to empty string if parsing fails.
   */
  function hostOf(u) {
    try {
      return new URL(u, location.href).hostname.replace(/^www\./, '');
    } catch (e) {
      return '';
    }
  }

  /**
   * Route decision for a given URL.
   * Returns an object:
   *   { kind: 'safe' | 'interstitial', url }
   * - 'safe': URL is in the allow-list (trusted domain).
   * - 'interstitial': URL is not trusted → show warning page first.
   */
  function route(url) {
    const h = hostOf(url);
    if (!h) return { kind: 'interstitial', url };  // Invalid or unparsable → block
    const safe = ALLOW.some(x => h === x || h.endsWith('.' + x));
    return { kind: safe ? 'safe' : 'interstitial', url };
  }

  // Expose ClickbaitDefense globally so other modules can use it
  window.ClickbaitDefense = { route, ALLOW };
})();
