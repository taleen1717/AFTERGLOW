/* AFTERGLOW Spotify AutoFill UI v2 — same-origin metadata only; no Spotify keys in browser. */
(() => {
  'use strict';

  function setupSpotifyAutoFill() {
    const urlField = document.querySelector('form.ag-spotify-form input[name="spotify_url"]');
    const form = urlField?.closest('form');
    const titleField = form?.querySelector('input[name="title"]');
    const artistField = form?.querySelector('input[name="artist"]');

    if (!form || !urlField || !titleField || !artistField) return;
    if (urlField.dataset.spotifyAutoFillReady === '1') return;
    urlField.dataset.spotifyAutoFillReady = '1';

    const endpoint = urlField.dataset.spotifyMetadataUrl || '/soundtrack/spotify-metadata';
    const status = document.createElement('p');
    status.className = 'ag-help ag-spotify-autofill-status';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    status.style.cssText = 'grid-column:1/-1;margin:7px 0 0;line-height:1.5;';
    urlField.closest('label')?.insertAdjacentElement('afterend', status);
    status.textContent = 'Spotify AutoFill ready — paste a Spotify link.';

    let debounce;
    let controller;
    let generation = 0;
    let latestUrl = '';
    let lastAutoTitle = '';
    let lastAutoArtist = '';

    function validSpotifyUrl(value) {
      try {
        const parsed = new URL(value);
        return parsed.protocol === 'https:' && parsed.hostname === 'open.spotify.com' &&
          /^\/(?:intl-[a-z]{2,5}\/)?(?:track|album|playlist|artist|episode|show)\/[A-Za-z0-9]{8,64}\/?$/i.test(parsed.pathname);
      } catch (_err) { return false; }
    }

    // Do not overwrite anything manually entered by the user.
    function canFill(field, previousAutoValue) {
      return field.value.trim() === '' || field.value === previousAutoValue;
    }

    async function lookup(url, currentGeneration) {
      controller = new AbortController();
      status.textContent = 'Reading Spotify details…';
      try {
        const response = await fetch(`${endpoint}?url=${encodeURIComponent(url)}`, {
          method: 'GET',
          credentials: 'same-origin',
          headers: { Accept: 'application/json' },
          signal: controller.signal,
        });
        const payload = await response.json().catch(() => ({}));
        if (generation !== currentGeneration) return;

        if (!response.ok) {
          status.textContent = response.status === 401 || response.status === 403
            ? 'Please sign in again to look up Spotify details.'
            : (payload.message || `Spotify lookup failed (HTTP ${response.status}). You can still enter details manually.`);
          return;
        }

        if (typeof payload.title === 'string' && payload.title.trim() && canFill(titleField, lastAutoTitle)) {
          titleField.value = payload.title.trim();
          lastAutoTitle = titleField.value;
          titleField.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (typeof payload.artist === 'string' && payload.artist.trim() && canFill(artistField, lastAutoArtist)) {
          artistField.value = payload.artist.trim();
          lastAutoArtist = artistField.value;
          artistField.dispatchEvent(new Event('change', { bubbles: true }));
        }
        status.textContent = payload.message || (payload.artist
          ? 'Song and artist filled. You can edit them.'
          : 'Title found. Artist information may require Spotify Web API access.');
      } catch (err) {
        if (err?.name !== 'AbortError' && generation === currentGeneration) {
          status.textContent = 'Could not reach the song lookup. Check browser Console / Network; you can still enter details manually.';
        }
      }
    }

    function refreshLookup() {
      const url = urlField.value.trim();
      if (url === latestUrl) return;
      latestUrl = url;
      generation++;
      clearTimeout(debounce);
      controller?.abort();
      if (!url) {
        status.textContent = 'Spotify AutoFill ready — paste a Spotify link.';
        return;
      }
      if (!validSpotifyUrl(url)) {
        status.textContent = 'Please paste a full Spotify Share URL from open.spotify.com.';
        return;
      }
      const currentGeneration = generation;
      debounce = setTimeout(() => lookup(url, currentGeneration), 350);
    }

    urlField.addEventListener('input', refreshLookup);
    urlField.addEventListener('change', refreshLookup);
    urlField.addEventListener('paste', () => setTimeout(refreshLookup, 0));
    urlField.addEventListener('blur', refreshLookup);
    refreshLookup();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupSpotifyAutoFill, { once: true });
  } else {
    setupSpotifyAutoFill();
  }
})();
