/* AFTERGLOW theme switcher — keeps Laravel routes and forms untouched. */
(function () {
  'use strict';
  const KEY = 'afterglow-world';
  const root = document.documentElement;
  function savedWorld() {
    try { return localStorage.getItem(KEY) === 'icy' ? 'icy' : 'cyber'; }
    catch (_) { return 'cyber'; }
  }
  function setWorld(world) {
    root.dataset.world = world;
    try { localStorage.setItem(KEY, world); } catch (_) {}
    const toggle = document.querySelector('.ag-world-toggle');
    if (toggle) {
      toggle.setAttribute('aria-label', world === 'cyber' ? 'Switch to Icy Ethereal theme' : 'Switch to Cyber City theme');
      toggle.setAttribute('aria-pressed', String(world === 'icy'));
      toggle.querySelector('.ag-toggle-label').textContent = world === 'cyber' ? '☾ Cyber Night' : '☀ Icy Ethereal';
    }
    const caption = document.querySelector('.ag-world-caption');
    if (caption) caption.textContent = world === 'cyber' ? 'WORLD 01 / CYBER CITY' : 'WORLD 02 / ICY ETHEREAL';
  }
  root.dataset.world = savedWorld();
  document.addEventListener('DOMContentLoaded', function () {
    const brand = document.querySelector('nav .site-name');
    if (brand) brand.textContent = 'AFTERGLOW';
    const navList = document.querySelector('nav ul');
    if (navList && !document.querySelector('.ag-world-toggle')) {
      const item = document.createElement('li');
      item.className = 'ag-toggle-item';
      item.innerHTML = '<button class="ag-world-toggle" type="button" aria-label="Switch theme" aria-pressed="false"><span class="ag-toggle-orb" aria-hidden="true"></span><span class="ag-toggle-label"></span> ⇄</button>';
      navList.appendChild(item);
      item.querySelector('button').addEventListener('click', function () {
        setWorld(root.dataset.world === 'cyber' ? 'icy' : 'cyber');
      });
    }
    if (location.pathname === '/' && !document.querySelector('.ag-hero')) {
      const main = document.querySelector('main');
      if (main) {
        const hero = document.createElement('section');
        hero.className = 'ag-hero';
        hero.setAttribute('aria-label', 'Welcome to AFTERGLOW');
        hero.innerHTML = '<div class="ag-hero-inner"><div class="ag-eyebrow">QUOTES · ART · MUSIC · MOODS</div><h1>AFTERGLOW</h1><p>Words for the late-night mind.</p><p class="ag-hero-desc">Two worlds, one place for your thoughts. Collect the words that feel like you.</p><div class="ag-hero-actions"><a href="#ag-quotes">Explore Quotes ↗</a><a href="#ag-quotes">Find your next thought ✦</a></div></div><span class="ag-world-caption"></span>';
        main.prepend(hero);
        const section = main.querySelector('section:not(.ag-hero)');
        if (section) section.id = 'ag-quotes';
      }
    }
    setWorld(root.dataset.world === 'icy' ? 'icy' : 'cyber');
  });
})();
