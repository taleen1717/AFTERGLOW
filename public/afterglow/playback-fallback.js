/* AFTERGLOW / Display a genuine fallback when the official YouTube player rejects a video.
 * Never extracts audio or hides the official player.
 */
(() => {
  'use strict';
  const cards = [...document.querySelectorAll('.ag-song-card')];
  const youtubePlayers = cards.map(card => ({
    card,
    details: card.querySelector('.ag-vp-details'),
    frame: card.querySelector('iframe[data-ag-youtube-player]')
  })).filter(item => item.frame && item.details);
  if (!youtubePlayers.length) return;

  let apiPromise;
  function youtubeAPI() {
    if (window.YT && typeof window.YT.Player === 'function') return Promise.resolve(window.YT);
    if (!apiPromise) {
      apiPromise = new Promise((resolve, reject) => {
        const previous = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = () => {
          if (typeof previous === 'function') previous();
          resolve(window.YT);
        };
        if (!document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
          const script = document.createElement('script');
          script.src = 'https://www.youtube.com/iframe_api';
          script.async = true;
          script.onerror = () => reject(new Error('YouTube player API failed to load'));
          document.head.appendChild(script);
        }
      });
    }
    return apiPromise;
  }

  youtubePlayers.forEach(({card, details, frame}) => {
    const help = details.querySelector('.ag-player-help-toggle');
    const fallback = details.querySelector('.ag-player-fallback');
    const title = details.querySelector('.ag-fallback-title');
    const alt = details.querySelector('.ag-try-alternative');
    const spotify = details.querySelector('.ag-spotify-fallback');
    const query = [card.dataset.songTitle, card.dataset.songArtist].filter(Boolean).join(' ').trim();
    let initialized = false;
    let errorDetected = false;

    const showFallback = (message, errored) => {
      if (fallback) fallback.hidden = false;
      if (title && message) title.textContent = message;
      if (help) help.setAttribute('aria-expanded', 'true');
      if (errored) {
        errorDetected = true;
        details.classList.add('ag-playback-failed');
        details.classList.remove('ag-vp-playing');
      }
    };

    if (spotify) spotify.href = 'https://open.spotify.com/search/' + encodeURIComponent(query);

    help?.addEventListener('click', () => {
      if (fallback.hidden) showFallback(null, false);
      else if (!errorDetected) {
        fallback.hidden = true;
        help.setAttribute('aria-expanded', 'false');
      }
    });

    alt?.addEventListener('click', () => {
      const youtubeTab = document.querySelector('.ag-source-tab[data-tab="youtube"]');
      const input = document.getElementById('ag-youtube-query');
      youtubeTab?.click();
      if (input) input.value = (query + ' official audio').trim();
      document.getElementById('song-add')?.scrollIntoView({behavior: 'smooth', block: 'start'});
      // Use the existing YouTube search flow; user chooses and saves their preferred recording.
      document.getElementById('ag-youtube-find')?.click();
      input?.focus({preventScroll: true});
    });

    async function attachPlayer() {
      if (initialized) return;
      initialized = true;
      // Set identity for this exact local origin, including port 8000 vs 8001.
      // Player remains fully visible, sized >= 200px, and is never detached on view switch.
      try {
        const source = new URL(frame.src, window.location.href);
        source.searchParams.set('enablejsapi', '1');
        source.searchParams.set('origin', window.location.origin);
        frame.src = source.toString();
        const YT = await youtubeAPI();
        new YT.Player(frame, {
          events: {
            onError: event => {
              const code = Number(event.data);
              const message = (code === 101 || code === 150)
                ? 'The owner has disabled playback on other websites.'
                : code === 100 ? 'This video was removed, private or unavailable.'
                : code === 153 ? 'YouTube could not verify this embed. Try another version or open YouTube.'
                : 'This video cannot play here right now.';
              showFallback(message, true);
            },
            onStateChange: event => {
              const playing = event.data === YT.PlayerState.PLAYING;
              details.classList.toggle('ag-vp-playing', playing);
              if (playing) {
                details.classList.remove('ag-playback-failed');
                errorDetected = false;
                if (fallback) fallback.hidden = true;
                help?.setAttribute('aria-expanded', 'false');
              }
            }
          }
        });
      } catch (error) {
        showFallback('Could not connect to the YouTube player. Try another version or open it on YouTube.', true);
      }
    }

    // Loading only when user opens the player, conserving resources for long libraries.
    details.addEventListener('toggle', () => {
      if (details.open) attachPlayer();
    });
    if (details.open) attachPlayer();
  });
})();
