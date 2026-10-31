/* AFTERGLOW My Soundtrack: switch presentation without replacing/reloading iframe */
(() => {
  'use strict';
  document.querySelectorAll('.ag-vp-details').forEach(details => {
    const stage = details.querySelector('[data-vp-stage]');
    const buttons = [...details.querySelectorAll('[data-vp-mode]')];
    if (!stage || buttons.length !== 2) return;
    buttons.forEach(button => button.addEventListener('click', () => {
      const isVinyl = button.dataset.vpMode === 'vinyl';
      stage.classList.toggle('is-vinyl', isVinyl);
      stage.classList.toggle('is-video', !isVinyl);
      buttons.forEach(b => {
        const selected = b === button;
        b.classList.toggle('is-active', selected);
        b.setAttribute('aria-pressed', String(selected));
      });
    }));
  });
})();
