/* Offline-friendly library search. No third-party data or audio is stored here. */
(function () {
  'use strict';
  const search = document.getElementById('soundtrack-search');
  const mood = document.getElementById('soundtrack-mood');
  const cards = Array.from(document.querySelectorAll('.ag-song-card'));
  const noMatch = document.getElementById('soundtrack-no-match');
  if (!search || !mood) return;
  const update = () => {
    const term = search.value.trim().toLowerCase();
    const selectedMood = mood.value;
    let visible = 0;
    for (const card of cards) {
      const ok = (!term || (card.dataset.query || '').includes(term)) &&
        (!selectedMood || card.dataset.mood === selectedMood);
      card.hidden = !ok;
      if (ok) visible++;
    }
    if (noMatch) noMatch.hidden = visible !== 0;
  };
  search.addEventListener('input', update);
  mood.addEventListener('change', update);
})();
