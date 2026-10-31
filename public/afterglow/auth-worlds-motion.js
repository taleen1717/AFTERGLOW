/* AFTERGLOW — Auth Worlds · Visual Experience v3
 * Presentational enhancements only. Never intercepts auth form events.
 * Replaces only the right-hand legacy promotional image on /signup and /login.
 */
(function () {
  'use strict';

  function el(tag, className, textContent) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (textContent !== undefined) element.textContent = textContent;
    return element;
  }

  function icon(type) {
    const ns = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '1.7');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    svg.setAttribute('aria-hidden', 'true');
    const strokes = {
      quote: [['path', {d:'M9 11H4V5h6v7c0 3.5-2.5 6-6 7'}], ['path', {d:'M20 11h-5V5h6v7c0 3.5-2.5 6-6 7'}]],
      music: [['path', {d:'M9 18V5l12-2v13'}], ['circle',{cx:'6',cy:'18',r:'3'}], ['circle',{cx:'18',cy:'16',r:'3'}]],
      art: [['rect',{x:'2.5',y:'3.5',width:'19',height:'17',rx:'3'}], ['circle',{cx:'8',cy:'9',r:'1.5'}], ['path',{d:'m3.5 17 5.2-5 4.1 3.5 3.1-3 4.7 5'}]],
      code: [['path',{d:'m8 7-5 5 5 5'}],['path',{d:'m16 7 5 5-5 5'}],['path',{d:'m14 4-4 16'}]],
      glow: [['path',{d:'M12 2.5 14.8 9l6.7 3-6.7 3L12 21.5 9.2 15l-6.7-3 6.7-3z'}],['path',{d:'M19 2v4M17 4h4'}]]
    };
    (strokes[type] || strokes.glow).forEach(function (entry) {
      const node = document.createElementNS(ns, entry[0]);
      Object.keys(entry[1]).forEach(function(key) { node.setAttribute(key, entry[1][key]); });
      svg.appendChild(node);
    });
    return svg;
  }

  function init() {
    const path = window.location.pathname.replace(/\/+$/, '') || '/';
    if (path !== '/signup' && path !== '/login') return;
    const main = document.querySelector('main');
    const form = main && main.querySelector('form');
    if (!main || !form) return;

    let grid = form.parentElement;
    while (grid && grid !== main && !(grid.classList.contains('grid') && grid.className.includes('grid-cols-2'))) {
      grid = grid.parentElement;
    }
    if (!grid || grid === main) {
      grid = form.parentElement;
      while (grid && grid !== main && !grid.classList.contains('grid')) grid = grid.parentElement;
    }
    if (!grid || grid === main) return;

    main.classList.add('ag-auth-main');
    grid.classList.add('ag-auth-two-col');
    if (!main.querySelector(':scope > .ag-auth-photo-motion')) {
      const photoLayer = el('div', 'ag-auth-photo-motion');
      photoLayer.setAttribute('aria-hidden', 'true');
      main.prepend(photoLayer);
    }
    // Purple-rain animation is decorative only; never changes any form.
    // The uploaded GIF has only one still frame, so these lightweight
    // CSS-animated streaks create actual visible falling-light motion.
    if (!main.querySelector('.ag-auth-purple-rain')) {
      const rain = el('div', 'ag-auth-purple-rain');
      rain.setAttribute('aria-hidden', 'true');
      for (let i = 0; i < 48; i++) {
        const drop = el('i', 'ag-auth-rain-drop');
        // Stable varied positions, durations, sizes and negative delays.
        drop.style.setProperty('--ag-drop-left', ((i * 43 + (i * i * 11) % 37) % 100) + '%');
        drop.style.setProperty('--ag-drop-height', (30 + (i * 17) % 110) + 'px');
        drop.style.setProperty('--ag-drop-width', (i % 6 === 0 ? 2 : 1) + 'px');
        drop.style.setProperty('--ag-drop-duration', (6 + (i * 7) % 9) + 's');
        drop.style.setProperty('--ag-drop-delay', (-((i * 13) % 23) - (i % 5) / 5) + 's');
        rain.appendChild(drop);
      }
      for (let i = 0; i < 22; i++) {
        const spark = el('i', 'ag-auth-rain-spark');
        spark.style.setProperty('--ag-spark-left', ((i * 47 + 7) % 100) + '%');
        spark.style.setProperty('--ag-spark-top', ((i * 31 + 13) % 93) + '%');
        spark.style.setProperty('--ag-spark-size', (2 + i % 3) + 'px');
        spark.style.setProperty('--ag-spark-duration', (2.5 + (i % 6) * .7) + 's');
        spark.style.setProperty('--ag-spark-delay', (-((i * 3) % 9)) + 's');
        rain.appendChild(spark);
      }
      main.appendChild(rain);
    }

    const formSide = grid.children[0];
    if (formSide) formSide.classList.add('ag-auth-form-side');
    let storySide = grid.children[1];
    if (!storySide) {
      storySide = el('div');
      grid.appendChild(storySide);
    }
    storySide.classList.add('ag-auth-story-side', 'ag-auth-story-v2');
    storySide.replaceChildren(); // Only former building photo/promo, never the form or navbar.

    // Purely decorative, noninteractive particles: no motion-sensitive content is required.
    const particles = el('div', 'ag-auth-sparkles');
    particles.setAttribute('aria-hidden', 'true');
    [
      [8,10,0],[86,2,1.1],[56,24,2],[95,44,0.7],[6,66,1.8],[42,88,0.4],[79,96,1.4]
    ].forEach(function(p, i) {
      const sparkle = el('i', 'ag-auth-sparkle');
      sparkle.style.setProperty('--x', p[0] + '%');
      sparkle.style.setProperty('--y', p[1] + '%');
      sparkle.style.setProperty('--delay', p[2] + 's');
      sparkle.style.setProperty('--size', (i % 3 === 0 ? 6 : 4) + 'px');
      particles.appendChild(sparkle);
    });
    storySide.appendChild(particles);

    const hero = el('div', 'ag-auth-promo-intro');
    const kickerRow = el('div', 'ag-auth-kicker-row');
    const kicker = el('span', 'ag-auth-story-kicker', 'WELCOME TO AFTERGLOW');
    const signal = el('span', 'ag-auth-signal');
    signal.appendChild(el('i', 'ag-auth-signal-dot'));
    signal.appendChild(el('span', '', 'YOUR SPACE IS HERE'));
    kickerRow.append(kicker, signal);
    const headline = el('h2', 'ag-auth-story-title');
    headline.append('More than ');
    headline.appendChild(el('span', 'ag-auth-gradient-word', 'words.'));
    const lead = el('p', 'ag-auth-story-lead', 'A place to collect the music you love, design the words you feel, share your art and exchange ideas.');
    const chips = el('div', 'ag-auth-promo-chips');
    ['CREATE', 'COLLECT', 'CONNECT'].forEach(function(label) {
      const chip = el('span', 'ag-auth-promo-chip', label);
      chips.appendChild(chip);
    });
    hero.append(kickerRow, headline, lead, chips);

    const featuresSection = el('section', 'ag-auth-feature-section');
    const headingRow = el('div', 'ag-auth-section-head');
    headingRow.append(el('span', '', 'EXPLORE YOUR UNIVERSE'));
    headingRow.append(el('span', 'ag-auth-section-line'));
    featuresSection.appendChild(headingRow);
    const list = el('ul', 'ag-auth-story-list');
    const services = [
      {name:'Quote Studio', desc:'Your words. Your moods. Your design.', icon:'quote', code:'WORDS'},
      {name:'My Soundtrack', desc:'Save your favorite tracks in Vinyl View.', icon:'music', code:'SOUND'},
      {name:'Visual Boards', desc:'A gallery for the art you make.', icon:'art', code:'VISUALS'},
      {name:'Code Exchange', desc:'Share snippets, tips and discoveries.', icon:'code', code:'IDEAS'},
      {name:'Daily Glow', desc:'One inspiring quote for every new day.', icon:'glow', code:'TODAY'}
    ];
    services.forEach(function(service, index) {
      const item = el('li', 'ag-auth-feature-card ag-auth-feature-' + service.icon);
      if (index === 4) item.classList.add('ag-auth-feature-wide');
      const iconWrap = el('span', 'ag-auth-feature-icon');
      iconWrap.appendChild(icon(service.icon));
      const copy = el('div', 'ag-auth-feature-copy');
      copy.appendChild(el('strong', '', service.name));
      copy.appendChild(el('span', '', service.desc));
      const symbol = el('span', 'ag-auth-feature-tag', service.code);
      item.append(iconWrap, copy, symbol);
      list.appendChild(item);
    });
    featuresSection.appendChild(list);
    const footer = el('div', 'ag-auth-promo-footer');
    const footerStar = el('span', 'ag-auth-footer-star', '✦');
    footer.append(footerStar, el('span', '', 'TWO WORLDS. ONE CREATIVE SPACE.'), el('span', 'ag-auth-footer-star', '✦'));
    storySide.append(hero, featuresSection, footer);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();


/* AFTERGLOW v5 · Decorative motion on all inner pages.
   No forms, API calls, navigation handlers, storage or route changes. */
(function () {
  'use strict';
  function setupInnerMotion() {
    const path = window.location.pathname.replace(/\/+$/, '') || '/';
    if (path === '/signup' || path === '/login' || document.querySelector('main.ag-auth-main')) return;
    if (document.getElementById('ag-inner-motion')) return;
    if (!document.querySelector('main')) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const root = document.createElement('div');
    root.id = 'ag-inner-motion';
    root.className = 'ag-inner-motion';
    root.setAttribute('aria-hidden', 'true');
    const dark = document.createElement('div');
    dark.className = 'ag-im-dark';
    const icy = document.createElement('div');
    icy.className = 'ag-im-icy';

    for (let i = 0; i < 30; i++) {
      const e = document.createElement('i');
      e.className = 'ag-im-drop';
      e.style.setProperty('--im-x', ((i * 43 + i * i * 7) % 100) + '%');
      e.style.setProperty('--im-w', (i % 8 === 0 ? 2 : 1) + 'px');
      e.style.setProperty('--im-h', (24 + i * 13 % 88) + 'px');
      e.style.setProperty('--im-dur', (8 + i * 3 % 9) + 's');
      e.style.setProperty('--im-delay', (-(i * 7 % 21)) + 's');
      dark.appendChild(e);
    }
    for (let i = 0; i < 15; i++) {
      const e = document.createElement('i');
      e.className = 'ag-im-star';
      e.style.setProperty('--im-x', ((i * 47 + 11) % 100) + '%');
      e.style.setProperty('--im-y', ((i * 31 + 23) % 100) + '%');
      e.style.setProperty('--im-size', (2 + i % 3) + 'px');
      e.style.setProperty('--im-dur', (3 + i % 5) + 's');
      e.style.setProperty('--im-delay', (-i / 2) + 's');
      dark.appendChild(e);
    }
    for (let i = 0; i < 2; i++) {
      const sweep = document.createElement('i');
      sweep.className = 'ag-im-chrome-sweep';
      icy.appendChild(sweep);
    }
    for (let i = 0; i < 16; i++) {
      const e = document.createElement('i');
      e.className = 'ag-im-glint';
      e.style.setProperty('--im-x', ((i * 41 + 5) % 100) + '%');
      e.style.setProperty('--im-y', ((i * 37 + 9) % 100) + '%');
      e.style.setProperty('--im-size', (2 + i % 3) + 'px');
      e.style.setProperty('--im-dur', (6 + i % 6 * 2) + 's');
      e.style.setProperty('--im-delay', (-i * 1.3) + 's');
      icy.appendChild(e);
    }
    root.append(dark, icy);
    document.body.prepend(root);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setupInnerMotion);
  else setupInnerMotion();
})();


/* AFTERGLOW v6 — animated light-world glass accents on auth only.
 * All existing v5 logic (including original services) remains unchanged. */
(function () {
  'use strict';
  function setup() {
    const path = window.location.pathname.replace(/\/+$/, '') || '/';
    if (path !== '/signup' && path !== '/login') return;
    const main = document.querySelector('main.ag-auth-main');
    if (!main || main.querySelector('.ag-v6-light-effects')) return;
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const layer = document.createElement('div');
    layer.className = 'ag-v6-light-effects';
    layer.setAttribute('aria-hidden', 'true');
    for (let i = 0; i < 20; i++) {
      const star = document.createElement('i');
      star.className = 'ag-v6-glint';
      star.style.setProperty('--v6-x', ((i * 43 + 11) % 97) + '%');
      star.style.setProperty('--v6-y', ((i * 29 + 7) % 92) + '%');
      star.style.setProperty('--v6-size', (i % 5 === 0 ? 4 : 2) + 'px');
      star.style.setProperty('--v6-duration', (7 + i % 9) + 's');
      star.style.setProperty('--v6-delay', (-(i * .86)) + 's');
      layer.appendChild(star);
    }
    for (let i = 0; i < 3; i++) {
      const ring = document.createElement('i');
      ring.className = 'ag-v6-orbit';
      ring.style.setProperty('--v6-x', (12 + i * 28) + '%');
      ring.style.setProperty('--v6-y', (18 + i * 24) + '%');
      ring.style.setProperty('--v6-i', String(i));
      layer.appendChild(ring);
    }
    main.appendChild(layer);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setup);
  else setup();
})();
