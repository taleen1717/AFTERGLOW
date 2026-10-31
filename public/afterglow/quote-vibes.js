/* AFTERGLOW — quote image previews and existing mood filter, no dependencies. */
(() => {
  'use strict';
  function init() {
    let imageMap = {};
    const imageData = document.getElementById('ag-vibe-image-data');
    if (imageData) {
      try { imageMap = JSON.parse(imageData.textContent); } catch (error) { console.warn('Quote images unavailable', error); }
    }

    const filters = document.getElementById('ag-mood-filters');
    if (filters) {
      const items = Array.from(document.querySelectorAll('.ag-quote-item'));
      const result = document.getElementById('ag-mood-results');
      const empty = document.getElementById('ag-mood-no-results');
      function applyMood(mood) {
        let count = 0;
        items.forEach(item => {
          const show = mood === 'all' || item.dataset.agQuoteMood === mood;
          item.hidden = !show;
          item.style.display = show ? '' : 'none';
          if (show) count++;
        });
        filters.querySelectorAll('[data-ag-mood]').forEach(button => {
          button.setAttribute('aria-pressed', String(button.dataset.agMood === mood));
        });
        if (result) result.textContent = count + (count === 1 ? ' quote' : ' quotes') + (mood === 'all' ? ' shown' : ' in this mood');
        if (empty) empty.classList.toggle('hidden', count !== 0 || items.length === 0);
      }
      filters.addEventListener('click', event => {
        const button = event.target.closest('[data-ag-mood]');
        if (button && filters.contains(button)) applyMood(button.dataset.agMood);
      });
      applyMood('all');
    }

    document.querySelectorAll('[data-ag-vibe-form]').forEach(form => {
      const moodSelect = form.querySelector('[data-ag-vibe-mood]');
      const styleRadios = Array.from(form.querySelectorAll('[name="card_style"]'));
      const styleFieldset = form.querySelector('[data-ag-vibe-choices]');
      const preview = form.querySelector('[data-ag-vibe-preview]');
      const previewMood = form.querySelector('[data-ag-vibe-preview-tag]');
      const previewText = form.querySelector('[data-ag-vibe-preview-text]');
      const textarea = form.querySelector('textarea[name="content"]');
      const fontSelect = form.querySelector('[data-ag-vibe-font]');
      const selectedColor = form.querySelector('[data-ag-selected-color]');
      const paletteButtons = Array.from(form.querySelectorAll('[data-ag-color-option]'));
      const customColor = form.querySelector('[data-ag-color-custom]');
      if (!moodSelect || !styleRadios.length || !preview) return;
      const selectedStyle = () => Number(styleRadios.find(input => input.checked)?.value || 1);
      const isHex = value => /^#[0-9a-f]{6}$/i.test(value);
      // WCAG relative luminance: light colors get a dark translucent chip, dark colors a light chip.
      function textPlate(hex) {
        const linear = [1,3,5].map(offset => {
          const value = parseInt(hex.slice(offset, offset + 2), 16) / 255;
          return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
        });
        const luminance = linear[0] * 0.2126 + linear[1] * 0.7152 + linear[2] * 0.0722;
        return luminance > 0.179 ? 'rgba(8,15,37,.94)' : 'rgba(250,252,255,.95)';
      }
      function setColor(value) {
        if (!selectedColor) return;
        selectedColor.value = value;
        render();
      }
      paletteButtons.forEach(button => button.addEventListener('click', () => setColor(button.dataset.agColorOption)));
      customColor?.addEventListener('input', () => setColor(customColor.value.toUpperCase()));

      function render() {
        const mood = moodSelect.value;
        const images = imageMap[mood] || [];
        const style = selectedStyle();
        const imageUrl = images[style - 1];
        styleFieldset.hidden = images.length === 0;
        styleRadios.forEach((input, index) => {
          const img = input.closest('.ag-vibe-choice')?.querySelector('[data-ag-vibe-thumb]');
          if (img) {
            if (images[index]) { img.src = images[index]; img.style.visibility = ''; }
            else { img.removeAttribute('src'); img.style.visibility = 'hidden'; }
          }
        });
        if (imageUrl) preview.style.setProperty('--ag-vibe-image', `url("${imageUrl}")`);
        else preview.style.removeProperty('--ag-vibe-image');
        preview.classList.toggle('ag-vibe-card--plain', !imageUrl);
        preview.dataset.agVibeMood = mood;
        if (previewMood) previewMood.textContent = moodSelect.selectedOptions[0]?.textContent || 'Just a Quote';
        if (previewText) previewText.textContent = '“' + (textarea?.value.trim() || 'Your quote will look like this...') + '”';
        preview.dataset.agTextFont = fontSelect?.value || 'modern';
        const color = selectedColor?.value || 'auto';
        preview.dataset.agTextColor = isHex(color) ? 'custom' : color;
        if (isHex(color)) {
          preview.style.setProperty('--ag-user-ink', color);
          preview.style.setProperty('--ag-user-plate', textPlate(color));
        } else {
          preview.style.removeProperty('--ag-user-ink');
          preview.style.removeProperty('--ag-user-plate');
        }
        paletteButtons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.agColorOption === color)));
        const customLabel = customColor?.closest('.ag-vibe-custom-color');
        if (customLabel) customLabel.classList.toggle('is-selected', isHex(color));
        if (isHex(color) && customColor && customColor.value.toLowerCase() !== color.toLowerCase()) customColor.value = color;
      }
      moodSelect.addEventListener('change', render);
      styleRadios.forEach(input => input.addEventListener('change', render));
      textarea?.addEventListener('input', render);
      fontSelect?.addEventListener('change', render);
      render();
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
