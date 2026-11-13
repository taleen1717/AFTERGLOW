/* Load embeds only after a user opens the public song. No private data or API keys. */
(() => {
  'use strict';
  document.querySelectorAll('[data-community-song]').forEach(details => {
    details.addEventListener('toggle', () => {
      if (!details.open) return;
      const frame = details.querySelector('iframe[data-community-src]');
      if (!frame || frame.hasAttribute('src')) return;
      // The server supplies validated YouTube/Spotify official embed URLs.
      const source = frame.dataset.communitySrc;
      try {
        const url = new URL(source, window.location.origin);
        if (url.protocol !== 'https:') return;
        const allowed = ['www.youtube-nocookie.com', 'open.spotify.com'];
        if (!allowed.includes(url.hostname)) return;
        frame.src = url.toString();
      } catch (_error) {
        /* Invalid URL: the external official-link fallback remains available. */
      }
    });
  });
})();
