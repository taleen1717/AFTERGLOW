/* AFTERGLOW — search checks eligibility and requires a successful preview play before saving.
 * Keeps the official YouTube video visible and never extracts audio.
 */
(() => {
  'use strict';
  const tabs = document.querySelectorAll('.ag-source-tab');
  const yt = document.getElementById('ag-youtube-panel');
  const sp = document.getElementById('ag-spotify-panel');
  if (!yt || !sp) return;

  for (const tab of tabs) tab.addEventListener('click', () => {
    const youtube = tab.dataset.tab === 'youtube';
    yt.hidden = !youtube;
    sp.hidden = youtube;
    tabs.forEach(b => {
      b.classList.toggle('is-active', b === tab);
      b.setAttribute('aria-selected', String(b === tab));
    });
  });

  const input = document.getElementById('ag-youtube-query');
  const find = document.getElementById('ag-youtube-find');
  const results = document.getElementById('ag-youtube-results');
  const status = document.getElementById('ag-youtube-status');
  const picked = document.getElementById('ag-youtube-picked');
  const videoId = document.getElementById('ag-video-id');
  const save = document.getElementById('ag-youtube-save-btn');
  const form = document.getElementById('ag-youtube-save');
  if (!input || !find || !results || !status || !picked || !videoId || !save || !form) return;

  const create = (tag, cls = '', text = null) => {
    const node = document.createElement(tag);
    if (cls) node.className = cls;
    if (text !== null) node.textContent = text;
    return node;
  };

  const preview = create('section', 'ag-precheck');
  preview.hidden = true;
  preview.setAttribute('aria-label', 'Verify song before adding');
  picked.insertAdjacentElement('afterend', preview);

  let busy = false;
  let generation = 0;
  let player = null;
  let confirmedVideo = '';
  let apiPromise = null;

  function reset() {
    generation++;
    confirmedVideo = '';
    videoId.value = '';
    save.disabled = true;
    save.textContent = 'Play the preview to unlock Add';
    try { player?.destroy(); } catch (_) { /* player may have been removed */ }
    player = null;
    picked.replaceChildren();
    picked.hidden = true;
    preview.replaceChildren();
    preview.hidden = true;
  }

  function youtubeAPI() {
    if (window.YT && typeof window.YT.Player === 'function') return Promise.resolve(window.YT);
    if (apiPromise) return apiPromise;
    apiPromise = new Promise((resolve, reject) => {
      let resolved = false;
      const timer = window.setTimeout(() => {
        if (!resolved) reject(new Error('YouTube preview did not load. Check connection or content blockers.'));
      }, 14000);
      const previous = window.onYouTubeIframeAPIReady;
      window.onYouTubeIframeAPIReady = () => {
        try { if (typeof previous === 'function') previous(); } catch (_) { /* other listener */ }
        resolved = true;
        window.clearTimeout(timer);
        if (window.YT && typeof window.YT.Player === 'function') resolve(window.YT);
        else reject(new Error('YouTube preview is unavailable.'));
      };
      if (!document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
        const script = document.createElement('script');
        script.src = 'https://www.youtube.com/iframe_api';
        script.async = true;
        script.onerror = () => { window.clearTimeout(timer); reject(new Error('YouTube preview could not load.')); };
        document.head.appendChild(script);
      }
    }).catch(error => { apiPromise = null; throw error; });
    return apiPromise;
  }

  async function selectSong(song) {
    reset();
    const ticket = generation;
    const image = create('img');
    image.src = song.artwork_url;
    image.alt = 'Selected video thumbnail';
    image.loading = 'lazy';
    const info = create('div');
    info.append(create('strong', '', song.title), create('p', '', song.artist));
    picked.append(image, info);
    picked.hidden = false;
    preview.hidden = false;

    const title = create('h3', '', 'Check playback before saving');
    const message = create('p', 'ag-precheck-status', 'This result passed YouTube availability filters. Press Play to confirm it works here before adding.');
    message.setAttribute('role', 'status');
    const playerHost = create('div', 'ag-precheck-player');
    const instruction = create('p', 'ag-precheck-note', 'Save becomes available only after the official YouTube player starts successfully.');
    preview.append(title, message, playerHost, instruction);
    status.textContent = 'Selected: ' + song.title + ' — verify playback before adding.';

    try {
      const YT = await youtubeAPI();
      if (ticket !== generation) return;
      player = new YT.Player(playerHost, {
        width: '100%',
        height: '240',
        videoId: song.video_id,
        playerVars: {playsinline: 1, origin: window.location.origin},
        events: {
          onError: event => {
            if (ticket !== generation) return;
            confirmedVideo = '';
            videoId.value = '';
            save.disabled = true;
            const code = Number(event.data);
            const detail = (code === 101 || code === 150)
              ? 'The owner does not allow embedded playback.'
              : code === 100 ? 'The video is unavailable or private.'
              : code === 153 ? 'YouTube could not validate this player.'
              : 'The video cannot play in the embedded player.';
            message.textContent = 'This recording cannot be played here. ' + detail + ' Choose another result — nothing has been added.';
            message.classList.add('ag-precheck-error');
            status.textContent = 'This result failed playback verification. Choose another.';
          },
          onStateChange: event => {
            if (ticket !== generation) return;
            if (event.data === YT.PlayerState.PLAYING) {
              confirmedVideo = song.video_id;
              videoId.value = song.video_id;
              save.disabled = false;
              save.textContent = '+ Add Verified Song';
              message.textContent = '✓ Playback verified in AFTERGLOW right now. You can add this recording.';
              message.classList.remove('ag-precheck-error');
              message.classList.add('ag-precheck-ok');
              status.textContent = 'Playback confirmed — ready to save ' + song.title;
            }
          }
        }
      });
    } catch (error) {
      if (ticket !== generation) return;
      message.textContent = 'Could not open the preview. Try another result or check your browser connection.';
      message.classList.add('ag-precheck-error');
    }
  }

  const search = async () => {
    if (busy) return;
    const q = input.value.trim();
    if (q.length < 3) { reset(); status.textContent = 'Type at least 3 characters to search.'; return; }
    busy = true;
    find.disabled = true;
    reset();
    const searchTicket = generation;
    results.replaceChildren();
    status.textContent = 'Searching for embeddable videos…';
    try {
      const response = await fetch(yt.dataset.youtubeUrl + '?q=' + encodeURIComponent(q), {headers: {'Accept': 'application/json'}});
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'Search unavailable.');
      if (searchTicket !== generation) return;
      const songs = Array.isArray(data.results) ? data.results : [];
      status.textContent = songs.length
        ? 'Likely blocked videos are hidden. Select a result and press Play — only verified playback unlocks Add.'
        : 'No eligible results were found. Try a different song or artist.';
      for (const song of songs) {
        const b = create('button', 'ag-discovery-result');
        b.type = 'button';
        const img = create('img');
        img.src = song.artwork_url;
        img.alt = '';
        img.loading = 'lazy';
        b.append(img, create('strong', '', song.title), create('small', '', song.artist));
        b.addEventListener('click', () => selectSong(song));
        results.append(b);
      }
    } catch (e) {
      if (searchTicket === generation) status.textContent = e.message || 'Search unavailable.';
    } finally {
      busy = false;
      find.disabled = false;
    }
  };

  find.addEventListener('click', search);
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); search(); }
  });
  form.addEventListener('submit', event => {
    if (save.disabled || !confirmedVideo || videoId.value !== confirmedVideo) {
      event.preventDefault();
      status.textContent = 'Play the preview successfully before adding this song.';
      return;
    }
    // Backend independently checks embed and region eligibility again at save time.
  });

  const spotifyQuery = document.getElementById('ag-spotify-search-text');
  const spotifyLink = document.getElementById('ag-spotify-search-link');
  if (spotifyQuery && spotifyLink) spotifyQuery.addEventListener('input', () => {
    spotifyLink.href = 'https://open.spotify.com/search/' + encodeURIComponent(spotifyQuery.value.trim());
  });
})();
