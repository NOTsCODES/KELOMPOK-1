/* =====================================================================
   Portofiles — interaksi & animasi (vanilla JS, tanpa library)
   Isi:
    1. Utilitas           9. Galeri: filter, sort, search, load more
    2. Tirai transisi    10. Like & follow
    3. Intro & reveal    11. Lightbox (detail karya)
    4. Kursor kustom     12. Dashboard: tabs, sidebar, counter
    5. Tombol magnetik   13. Form: password, loading, upload
    6. Tilt kartu        14. Halaman artists: gambar hover
    7. Navigasi          15. Toast
    8. Menu dropdown
   ===================================================================== */
(() => {
  'use strict';

  /* ---------------- 1. Utilitas ---------------- */
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];
  const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const finePointer = matchMedia('(hover: hover) and (pointer: fine)').matches;
  const lerp = (a, b, t) => a + (b - a) * t;
  const clamp = (v, min, max) => Math.min(max, Math.max(min, v));
  const csrf = $('meta[name="csrf-token"]')?.content || '';
  const base = $('meta[name="base-path"]')?.content || '';
  const api = (path) => `${base}/${path}`;
  const html = document.documentElement;
  const body = document.body;

  async function post(path, data) {
    const fd = new FormData();
    Object.entries(data).forEach(([k, v]) => fd.append(k, v));
    fd.append('csrf', csrf);
    const res = await fetch(api(path), { method: 'POST', body: fd, headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' });
    const json = await res.json().catch(() => ({}));
    return { ok: res.ok, status: res.status, data: json };
  }

  /* ---------------- 15. Toast (dipakai banyak bagian) ---------------- */
  const toastBox = $('.toasts');
  function toast(message, type = 'info') {
    if (!toastBox) return;
    const t = document.createElement('div');
    t.className = `toast toast--${type}`;
    t.textContent = message;
    toastBox.appendChild(t);
    setTimeout(() => {
      t.classList.add('is-leaving');
      t.addEventListener('animationend', () => t.remove(), { once: true });
    }, 3600);
  }
  window.toast = toast;
  // Pesan flash dari PHP
  $$('template.flash').forEach((tpl, i) => setTimeout(() => toast(tpl.content.textContent, tpl.dataset.type), 900 + i * 250));

  /* ---------------- 2. Tirai transisi antar halaman ---------------- */
  const curtain = $('.curtain');
  function revealPage() {
    body.classList.add('is-loaded');
    try { sessionStorage.removeItem('pf-nav'); sessionStorage.setItem('pf-seen', '1'); } catch (e) {}
    if (!html.classList.contains('pf-cover')) return;
    const firstVisit = !sessionStorage.getItem('pf-was-here');
    try { sessionStorage.setItem('pf-was-here', '1'); } catch (e) {}
    // Kunjungan pertama: tahan sebentar agar logo terlihat
    setTimeout(() => {
      html.classList.remove('pf-cover');
      curtain.classList.add('is-leaving');
      setTimeout(() => curtain.classList.remove('is-leaving'), 1100);
    }, firstVisit ? 650 : 80);
  }
  // Tunggu font siap agar judul tidak "lompat"
  const ready = document.fonts?.ready || Promise.resolve();
  Promise.race([ready, new Promise((r) => setTimeout(r, 900))]).then(() => requestAnimationFrame(revealPage));

  // Saat kembali dengan tombol Back (bfcache), pastikan tirai terbuka
  addEventListener('pageshow', (e) => {
    if (e.persisted) {
      curtain.classList.remove('is-entering');
      html.classList.remove('pf-cover');
      body.classList.add('is-loaded');
    }
  });

  function shouldAnimateLink(a, e) {
    if (calm || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return false;
    if (a.target && a.target !== '_self') return false;
    if (a.hasAttribute('download') || a.dataset.noTransition !== undefined) return false;
    const url = new URL(a.href, location.href);
    if (url.origin !== location.origin) return false;
    if (url.protocol !== 'http:' && url.protocol !== 'https:') return false;
    if (url.pathname === location.pathname && url.search === location.search && url.hash) return false;
    if (a.getAttribute('href') === '#' || a.getAttribute('href')?.startsWith('#')) return false;
    return true;
  }
  function leaveTo(href) {
    try { sessionStorage.setItem('pf-nav', '1'); } catch (e) {}
    curtain.classList.remove('is-leaving');
    curtain.classList.add('is-entering');
    setTimeout(() => { location.href = href; }, 650);
  }
  document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a || !shouldAnimateLink(a, e)) return;
    e.preventDefault();
    leaveTo(a.href);
  });
  // Form biasa (bukan AJAX) juga memakai tirai setelah submit
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (e.defaultPrevented || calm || form.dataset.noTransition !== undefined) return;
    const btn = e.submitter && e.submitter.classList.contains('btn') ? e.submitter : form.querySelector('button[type="submit"].btn');
    if (btn) btn.classList.add('is-loading');
    try { sessionStorage.setItem('pf-nav', '1'); } catch (err) {}
  });

  /* ---------------- 3. Reveal saat scroll ---------------- */
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-in');
      revealObserver.unobserve(entry.target);
      entry.target.dispatchEvent(new CustomEvent('reveal'));
    });
  }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
  function observeReveals(root = document) {
    $$('[data-reveal]:not(.is-in)', root).forEach((el) => revealObserver.observe(el));
  }
  observeReveals();

  // Progress bar scroll di garis navigasi
  const progressEl = $('[data-scroll-progress]');

  /* ---------------- 4. Kursor kustom ---------------- */
  const cursor = $('.cursor');
  if (finePointer && !calm && cursor) {
    html.classList.add('has-cursor');
    const dot = $('.cursor__dot', cursor);
    const ring = $('.cursor__ring', cursor);
    const label = $('.cursor__label', cursor);
    let mx = innerWidth / 2, my = innerHeight / 2, rx = mx, ry = my, shown = false;

    addEventListener('mousemove', (e) => {
      mx = e.clientX; my = e.clientY;
      if (!shown) { rx = mx; ry = my; shown = true; cursor.classList.remove('is-hidden'); }
      dot.style.transform = `translate3d(${mx}px, ${my}px, 0)`;
    }, { passive: true });
    document.addEventListener('mouseleave', () => cursor.classList.add('is-hidden'));
    document.addEventListener('mouseenter', () => cursor.classList.remove('is-hidden'));
    addEventListener('mousedown', () => cursor.classList.add('is-down'));
    addEventListener('mouseup', () => cursor.classList.remove('is-down'));

    (function loop() {
      rx = lerp(rx, mx, 0.18); ry = lerp(ry, my, 0.18);
      ring.style.transform = `translate3d(${rx}px, ${ry}px, 0)`;
      requestAnimationFrame(loop);
    })();

    document.addEventListener('mouseover', (e) => {
      const view = e.target.closest('[data-cursor="view"]');
      const hover = e.target.closest('a, button, label, input[type="checkbox"], [data-menu-toggle]');
      cursor.classList.toggle('is-view', !!view);
      cursor.classList.toggle('is-hover', !view && !!hover);
      if (view) label.textContent = view.dataset.cursorLabel || 'View';
    });
  }

  /* ---------------- 5. Tombol magnetik ---------------- */
  if (finePointer && !calm) {
    $$('[data-magnetic]').forEach((el) => {
      const strength = el.classList.contains('logo') ? 0.15 : 0.28;
      el.addEventListener('mousemove', (e) => {
        const r = el.getBoundingClientRect();
        const x = e.clientX - (r.left + r.width / 2);
        const y = e.clientY - (r.top + r.height / 2);
        el.style.transform = `translate(${x * strength}px, ${y * strength * 1.2}px)`;
      });
      el.addEventListener('mouseleave', () => { el.style.transform = ''; });
    });
  }

  /* ---------------- 6. Tilt & parallax kartu ---------------- */
  function bindTilt(root = document) {
    if (!finePointer || calm) return;
    $$('.art-card__media:not([data-tilt-bound])', root).forEach((media) => {
      media.dataset.tiltBound = '1';
      media.addEventListener('mousemove', (e) => {
        const r = media.getBoundingClientRect();
        const px = (e.clientX - r.left) / r.width;   // 0..1
        const py = (e.clientY - r.top) / r.height;
        media.style.setProperty('--ry', `${(px - 0.5) * 7}deg`);
        media.style.setProperty('--rx', `${(0.5 - py) * 7}deg`);
        media.style.setProperty('--px', `${(0.5 - px) * 14}px`);
        media.style.setProperty('--py', `${(0.5 - py) * 10}px`);
        media.style.setProperty('--mx', `${px * 100}%`);
        media.style.setProperty('--my', `${py * 100}%`);
      });
      media.addEventListener('mouseleave', () => {
        ['--rx', '--ry', '--px', '--py'].forEach((p) => media.style.removeProperty(p));
      });
    });
  }
  bindTilt();

  // Parallax gambar di halaman login (mengikuti mouse)
  const pFrame = $('[data-parallax-frame]');
  if (pFrame && finePointer && !calm) {
    const img = $('[data-parallax-img]', pFrame);
    $('.auth').addEventListener('mousemove', (e) => {
      const x = e.clientX / innerWidth - 0.5;
      const y = e.clientY / innerHeight - 0.5;
      img.style.setProperty('--px', `${-x * 22}px`);
      img.style.setProperty('--py', `${-y * 16}px`);
    });
  }

  /* ---------------- 7. Navigasi: sembunyi saat scroll turun ---------------- */
  const nav = $('[data-nav]');
  let lastY = scrollY, ticking = false;
  function onScroll() {
    const y = scrollY;
    if (nav) {
      nav.classList.toggle('is-scrolled', y > 10);
      const goingDown = y > lastY && y > 240;
      if (!body.classList.contains('menu-open') && !$('.menu.is-open')) nav.classList.toggle('is-hidden-up', goingDown);
    }
    if (progressEl) {
      const max = document.documentElement.scrollHeight - innerHeight;
      progressEl.style.setProperty('--progress', max > 0 ? clamp(y / max, 0, 1) : 0);
    }
    lastY = y;
    ticking = false;
  }
  addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
  onScroll();

  // Menu mobile
  $('[data-burger]')?.addEventListener('click', () => body.classList.toggle('menu-open'));

  // Tekan "/" untuk fokus ke pencarian
  document.addEventListener('keydown', (e) => {
    if (e.key === '/' && !/input|textarea|select/i.test(document.activeElement.tagName)) {
      const s = $('.search__input');
      if (s && s.offsetParent) { e.preventDefault(); s.focus(); }
    }
  });

  /* ---------------- 8. Menu dropdown ---------------- */
  document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-menu-toggle]');
    const openMenus = $$('.menu.is-open');
    if (toggle) {
      const menu = toggle.closest('[data-menu]');
      openMenus.forEach((m) => { if (m !== menu) closeMenu(m); });
      menu.classList.contains('is-open') ? closeMenu(menu) : openMenu(menu);
      return;
    }
    if (!e.target.closest('.menu__panel')) openMenus.forEach(closeMenu);
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') $$('.menu.is-open').forEach(closeMenu); });
  function openMenu(m) { m.classList.add('is-open'); $('[data-menu-toggle]', m)?.setAttribute('aria-expanded', 'true'); }
  function closeMenu(m) { m.classList.remove('is-open'); $('[data-menu-toggle]', m)?.setAttribute('aria-expanded', 'false'); }

  // Konfirmasi sebelum hapus
  document.addEventListener('submit', (e) => {
    const msg = e.target.dataset.confirm;
    if (msg && !confirm(msg)) { e.preventDefault(); e.stopImmediatePropagation(); }
  }, true);

  /* ---------------- 9. Galeri: filter, sort, search, load more ---------------- */
  const gallery = $('[data-gallery]');
  const chips = $('[data-chips]');

  // Pil hitam yang meluncur di belakang chip aktif
  function movePill(container, pill, active) {
    if (!container || !pill || !active) return;
    pill.style.width = `${active.offsetWidth}px`;
    pill.style.transform = `translate(${active.offsetLeft}px, ${active.offsetTop}px)`;
  }
  if (chips) {
    const pill = $('[data-chips-pill]', chips);
    const place = () => movePill(chips, pill, $('.chip.is-active', chips));
    place();
    requestAnimationFrame(() => chips.classList.add('is-ready'));
    addEventListener('resize', place);
    document.fonts?.ready.then(place);
  }

  if (gallery) {
    const state = {
      category: gallery.dataset.filterCategory,
      sort: gallery.dataset.filterSort,
      q: gallery.dataset.filterQ,
      offset: Number(gallery.dataset.offset) || 0,
      busy: false,
    };
    const moreBtn = $('[data-load-more]');
    const moreNote = $('[data-more-note]');
    const empty = $('[data-empty]');
    const label = $('[data-results-label]');
    const defaultLabel = 'Fresh perspectives, thoughtfully selected.';

    function syncUrl() {
      const p = new URLSearchParams();
      if (state.category && state.category !== 'for-you') p.set('category', state.category);
      if (state.sort && state.sort !== 'curated') p.set('sort', state.sort);
      if (state.q) p.set('q', state.q);
      history.replaceState(null, '', `${location.pathname}${p.toString() ? '?' + p : ''}`);
    }

    async function fetchCards(offset) {
      const p = new URLSearchParams({ category: state.category, sort: state.sort, q: state.q, offset });
      const res = await fetch(`${api('api/artworks.php')}?${p}`, { credentials: 'same-origin' });
      if (!res.ok) throw new Error('Request failed');
      return res.json();
    }

    function insertCards(htmlStr) {
      const tpl = document.createElement('template');
      tpl.innerHTML = htmlStr.trim();
      const cards = [...tpl.content.children];
      gallery.append(...cards);
      bindTilt(gallery);
      observeReveals(gallery);
      return cards;
    }

    function setMore(hasMore) {
      moreBtn.classList.toggle('is-hidden', !hasMore);
      moreNote.textContent = hasMore ? 'A new perspective is always one scroll away.' : (state.offset ? 'You’ve seen it all, for now.' : '');
    }

    // Ganti seluruh isi galeri (filter/sort/search)
    async function reload() {
      if (state.busy) return;
      state.busy = true;
      syncUrl();
      const old = $$('.art-card', gallery);
      old.forEach((c) => c.classList.add('is-leaving'));
      const wait = new Promise((r) => setTimeout(r, old.length && !calm ? 380 : 0));
      try {
        const [data] = await Promise.all([fetchCards(0), wait]);
        // Kunci tinggi agar halaman tidak meloncat
        gallery.style.minHeight = `${gallery.offsetHeight}px`;
        old.forEach((c) => c.remove());
        state.offset = data.count;
        insertCards(data.html);
        empty.classList.toggle('is-visible', data.count === 0);
        setMore(data.hasMore);
        if (label) label.textContent = state.q ? `Results for “${state.q}”` : defaultLabel;
        requestAnimationFrame(() => { gallery.style.minHeight = ''; });
      } catch (err) {
        old.forEach((c) => c.classList.remove('is-leaving'));
        toast('Could not load artworks. Check your connection.', 'error');
      } finally {
        state.busy = false;
      }
    }

    chips?.addEventListener('click', (e) => {
      const chip = e.target.closest('.chip');
      if (!chip || chip.classList.contains('is-active')) return;
      $$('.chip', chips).forEach((c) => { c.classList.toggle('is-active', c === chip); c.setAttribute('aria-selected', c === chip); });
      movePill(chips, $('[data-chips-pill]', chips), chip);
      state.category = chip.dataset.category;
      reload();
    });

    $$(".menu__panel [data-sort]").forEach((btn) => btn.addEventListener('click', () => {
      state.sort = btn.dataset.sort;
      $$(".menu__panel [data-sort]").forEach((b) => b.setAttribute('aria-checked', b === btn));
      $('[data-sort-label]').textContent = btn.textContent;
      closeMenu(btn.closest('[data-menu]'));
      reload();
    }));

    // Pencarian langsung (tanpa reload halaman)
    const searchForm = $('.nav .search');
    const searchInput = $('.search__input', searchForm || document);
    let searchTimer;
    searchInput?.addEventListener('input', () => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        state.q = searchInput.value.trim();
        reload();
        if (state.q && scrollY < 300) $('#explore')?.scrollIntoView({ behavior: calm ? 'auto' : 'smooth' });
      }, 350);
    });
    searchForm?.addEventListener('submit', (e) => { e.preventDefault(); clearTimeout(searchTimer); state.q = searchInput.value.trim(); reload(); $('#explore')?.scrollIntoView({ behavior: 'smooth' }); });

    moreBtn?.addEventListener('click', async () => {
      if (state.busy) return;
      state.busy = true;
      moreBtn.classList.add('is-loading');
      try {
        const data = await fetchCards(state.offset);
        state.offset += data.count;
        const cards = insertCards(data.html);
        setMore(data.hasMore);
        // Gulir lembut ke kartu baru
        if (cards[0] && !calm) setTimeout(() => window.scrollBy({ top: Math.min(320, cards[0].getBoundingClientRect().top - innerHeight * 0.45), behavior: 'smooth' }), 120);
      } catch (err) {
        toast('Could not load more work.', 'error');
      } finally {
        moreBtn.classList.remove('is-loading');
        state.busy = false;
      }
    });
  }

  /* ---------------- 10. Like & follow ---------------- */
  function burst(btn) {
    if (calm) return;
    const b = document.createElement('span');
    b.className = 'burst';
    const colors = ['#ff5a5f', '#e1ff62', '#20211d', '#ffb86b'];
    for (let i = 0; i < 10; i++) {
      const dot = document.createElement('i');
      const ang = (Math.PI * 2 * i) / 10;
      const dist = 16 + Math.random() * 10;
      dot.style.setProperty('--x', `${Math.cos(ang) * dist}px`);
      dot.style.setProperty('--y', `${Math.sin(ang) * dist}px`);
      dot.style.setProperty('--c', colors[i % colors.length]);
      b.appendChild(dot);
    }
    btn.appendChild(b);
    setTimeout(() => b.remove(), 800);
  }

  function setLikeUI(btn, liked, count) {
    btn.classList.toggle('is-liked', liked);
    btn.setAttribute('aria-pressed', liked);
    const c = $('.like-btn__count', btn);
    if (count !== undefined && c.textContent !== count.toLocaleString('en-US')) {
      c.textContent = count.toLocaleString('en-US');
      c.classList.remove('tick'); void c.offsetWidth; c.classList.add('tick');
    }
  }

  async function toggleLike(id, btns) {
    const wasLiked = btns[0].classList.contains('is-liked');
    const current = Number($('.like-btn__count', btns[0]).textContent.replace(/,/g, '')) || 0;
    // Optimistic UI: langsung berubah, lalu disesuaikan dengan server
    btns.forEach((b) => {
      setLikeUI(b, !wasLiked, Math.max(0, current + (wasLiked ? -1 : 1)));
      if (!wasLiked) { b.classList.remove('pop'); void b.offsetWidth; b.classList.add('pop'); burst(b); }
    });
    const { ok, status, data } = await post('api/like.php', { artwork_id: id });
    if (status === 401) {
      btns.forEach((b) => setLikeUI(b, wasLiked, current));
      toast('Log in to appreciate this piece.');
      setTimeout(() => leaveTo(`${data.login}?next=${encodeURIComponent(location.pathname + location.search)}`), 900);
      return;
    }
    if (!ok) {
      btns.forEach((b) => setLikeUI(b, wasLiked, current));
      toast(data.error || 'Something went wrong.', 'error');
      return;
    }
    btns.forEach((b) => setLikeUI(b, data.liked, data.count));
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-like]');
    if (!btn) return;
    e.preventDefault();
    const id = btn.dataset.like;
    toggleLike(id, $$(`[data-like="${id}"]`));
  });

  $$('[data-follow]').forEach((btn) => btn.addEventListener('click', async () => {
    btn.classList.add('is-loading');
    const { ok, status, data } = await post('api/follow.php', { user_id: btn.dataset.follow });
    btn.classList.remove('is-loading');
    if (status === 401) { toast('Log in to follow artists.'); setTimeout(() => leaveTo(`${data.login}?next=${encodeURIComponent(location.pathname + location.search)}`), 900); return; }
    if (!ok) { toast(data.error || 'Something went wrong.', 'error'); return; }
    btn.setAttribute('aria-pressed', data.following);
    btn.classList.toggle('btn--dark', !data.following);
    btn.classList.toggle('btn--outline', data.following);
    $('.btn__label', btn).textContent = data.following ? 'Following' : 'Follow';
    const countEl = $('[data-follow-count]');
    if (countEl) countEl.textContent = data.count.toLocaleString('en-US');
    if (data.following) toast('You’ll see their new work first.');
  }));

  /* ---------------- 11. Lightbox (FLIP dari kartu ke layar penuh) ---------------- */
  const lb = $('[data-lightbox]');
  if (lb) {
    const lbImg = $('[data-lightbox-img]', lb);
    const lbLike = $('[data-lb-like]', lb);
    let currentCard = null;

    const fill = (card) => {
      const d = card.dataset;
      $('[data-lb-category]', lb).textContent = d.category;
      $('[data-lb-title]', lb).textContent = d.title;
      const prof = $('[data-lb-profile]', lb);
      prof.textContent = d.artist; prof.href = d.profile;
      $('[data-lb-desc]', lb).textContent = d.desc;
      $('[data-lb-views]', lb).textContent = Number(d.views).toLocaleString('en-US');
      const src = $('[data-like]', card);
      lbLike.dataset.like = d.id;
      setLikeUI(lbLike, src.classList.contains('is-liked'), Number($('.like-btn__count', src).textContent.replace(/,/g, '')));
      lbImg.src = d.image;
      lbImg.alt = `${d.title} by ${d.artist}`;
      // Catat view
      post('api/view.php', { artwork_id: d.id }).then(({ ok, data }) => {
        if (ok && data.views !== undefined) { $('[data-lb-views]', lb).textContent = data.views.toLocaleString('en-US'); card.dataset.views = data.views; }
      });
    };

    // Animasi gambar "terbang" dari kartu ke posisi lightbox
    function flip(fromRect, toEl, reverse = false) {
      return new Promise((resolve) => {
        if (calm) return resolve();
        const toRect = toEl.getBoundingClientRect();
        const ghost = document.createElement('img');
        ghost.className = 'lightbox-ghost';
        ghost.src = lbImg.src;
        const [a, b] = reverse ? [toRect, fromRect] : [fromRect, toRect];
        Object.assign(ghost.style, { left: `${b.left}px`, top: `${b.top}px`, width: `${b.width}px`, height: `${b.height}px` });
        document.body.appendChild(ghost);
        const sx = a.width / b.width, sy = a.height / b.height;
        const anim = ghost.animate([
          { transform: `translate(${a.left - b.left}px, ${a.top - b.top}px) scale(${sx}, ${sy})`, transformOrigin: 'top left', borderRadius: reverse ? '10px' : '8px' },
          { transform: 'none', transformOrigin: 'top left', borderRadius: reverse ? '8px' : '10px' },
        ], { duration: 820, easing: 'cubic-bezier(.16,1,.3,1)' });
        anim.onfinish = () => { ghost.remove(); resolve(); };
      });
    }

    async function open(card) {
      currentCard = card;
      const media = $('.art-card__media', card);
      fill(card);
      body.classList.add('lightbox-open');
      lb.setAttribute('aria-hidden', 'false');
      await (lbImg.complete ? Promise.resolve() : new Promise((r) => { lbImg.onload = r; lbImg.onerror = r; }));
      lbImg.style.visibility = 'hidden';
      lb.classList.add('is-open');
      media.style.visibility = 'hidden';
      await flip(media.getBoundingClientRect(), lbImg);
      lbImg.style.visibility = '';
      media.style.visibility = '';
      $('.lightbox__close', lb).focus({ preventScroll: true });
    }

    async function close() {
      if (!lb.classList.contains('is-open')) return;
      const media = currentCard && $('.art-card__media', currentCard);
      const rect = media?.getBoundingClientRect();
      const visible = rect && rect.bottom > 0 && rect.top < innerHeight;
      lb.classList.remove('is-open');
      if (visible) {
        media.style.visibility = 'hidden';
        lbImg.style.visibility = 'hidden';
        await flip(rect, lbImg, true).catch(() => {});
        media.style.visibility = '';
        lbImg.style.visibility = '';
      }
      lb.setAttribute('aria-hidden', 'true');
      body.classList.remove('lightbox-open');
      currentCard?.querySelector('.art-card__media')?.focus({ preventScroll: true });
    }

    function step(dir) {
      const cards = $$('.art-card');
      if (!currentCard || cards.length < 2) return;
      const i = cards.indexOf(currentCard);
      const next = cards[(i + dir + cards.length) % cards.length];
      currentCard = next;
      fill(next);
      lbImg.style.setProperty('--dir', `${dir * 40}px`);
      lbImg.classList.remove('is-swapping'); void lbImg.offsetWidth; lbImg.classList.add('is-swapping');
      $$('.lightbox__panel > *', lb).forEach((el) => { el.animate([{ opacity: 0, transform: 'translateY(12px)' }, { opacity: 1, transform: 'none' }], { duration: 500, easing: 'cubic-bezier(.16,1,.3,1)' }); });
      next.scrollIntoView({ block: 'center', behavior: 'auto' });
    }

    document.addEventListener('click', (e) => {
      const trigger = e.target.closest('[data-open-artwork]');
      if (trigger) { e.preventDefault(); open(trigger.closest('.art-card')); }
      if (e.target.closest('[data-lightbox-close]')) close();
      if (e.target.closest('[data-lb-prev]')) step(-1);
      if (e.target.closest('[data-lb-next]')) step(1);
    });
    document.addEventListener('keydown', (e) => {
      if (!lb.classList.contains('is-open')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowRight') step(1);
      if (e.key === 'ArrowLeft') step(-1);
    });
  }

  /* ---------------- 12. Dashboard ---------------- */
  // Angka yang menghitung naik
  function countUp(el) {
    const target = Number(el.dataset.count) || 0;
    if (calm || target === 0) { el.textContent = target.toLocaleString('en-US'); return; }
    const dur = 1400 + Math.min(target, 3000) / 3;
    const start = performance.now();
    const ease = (t) => 1 - Math.pow(1 - t, 4);
    (function tick(now) {
      const t = clamp((now - start) / dur, 0, 1);
      el.textContent = Math.round(target * ease(t)).toLocaleString('en-US');
      if (t < 1) requestAnimationFrame(tick);
    })(start);
  }
  const countObserver = new IntersectionObserver((entries) => entries.forEach((en) => {
    if (en.isIntersecting) { countUp(en.target); countObserver.unobserve(en.target); }
  }), { threshold: 0.5 });
  $$('[data-count]').forEach((el) => { if (!calm) el.textContent = '0'; countObserver.observe(el); });

  // Progress bar profil
  const progObserver = new IntersectionObserver((entries) => entries.forEach((en) => {
    if (en.isIntersecting) { en.target.style.width = `${en.target.dataset.progress}%`; progObserver.unobserve(en.target); }
  }), { threshold: 0.6 });
  $$('[data-progress]').forEach((el) => progObserver.observe(el));

  // Pil sidebar
  const sideNav = $('[data-side-nav]');
  if (sideNav) {
    const pill = $('[data-side-pill]', sideNav);
    const links = $$('a', sideNav);
    const placeSide = (a) => { if (a) pill.style.transform = `translateY(${a.offsetTop}px)`; };
    const active = $('a.is-active', sideNav);
    placeSide(active);
    requestAnimationFrame(() => sideNav.classList.add('is-ready'));
    links.forEach((a) => {
      a.addEventListener('mouseenter', () => placeSide(a));
      a.addEventListener('focus', () => placeSide(a));
    });
    sideNav.addEventListener('mouseleave', () => placeSide(active));
  }

  // Tabs project dengan garis yang meluncur
  $$('[data-tabs]').forEach((tabs) => {
    const line = $('[data-tabs-line]', tabs);
    const grid = tabs.parentElement.querySelector('[data-project-grid]');
    const limit = Number(grid?.dataset.limit) || Infinity;
    const placeLine = (t) => { line.style.width = `${t.offsetWidth}px`; line.style.transform = `translateX(${t.offsetLeft}px)`; };
    placeLine($('.tab.is-active', tabs));
    addEventListener('resize', () => placeLine($('.tab.is-active', tabs)));

    function apply(filter, animate) {
      if (!grid) return;
      grid.classList.add('is-managed');
      let shown = 0;
      $$('.project-card', grid).forEach((card) => {
        const match = filter === 'all' || card.dataset.status === filter;
        const visible = match && shown < limit;
        if (visible) shown++;
        card.classList.toggle('is-filtered', !visible);
        if (visible && animate) {
          card.classList.add('is-in');
          card.style.setProperty('--n', shown - 1);
          card.classList.remove('is-entering'); void card.offsetWidth; card.classList.add('is-entering');
        }
      });
    }
    apply('all', false);

    tabs.addEventListener('click', (e) => {
      const tab = e.target.closest('.tab');
      if (!tab) return;
      $$('.tab', tabs).forEach((t) => { t.classList.toggle('is-active', t === tab); t.setAttribute('aria-selected', t === tab); });
      placeLine(tab);
      apply(tab.dataset.filter, true);
    });
  });

  // Salin link portofolio
  $$('[data-copy]').forEach((el) => el.addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(location.protocol + el.dataset.copy); toast('Link copied. Go share it.'); }
    catch (e) { toast('Could not copy the link.', 'error'); }
  }));

  /* ---------------- 13. Form ---------------- */
  $$('[data-toggle-password]').forEach((btn) => btn.addEventListener('click', () => {
    const input = document.getElementById(btn.getAttribute('aria-controls'));
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? 'Hide' : 'Show';
    input.focus();
  }));

  // Indikator kekuatan password
  $$('[data-strength]').forEach((input) => {
    const bar = input.closest('.field').querySelector('[data-strength-bar]');
    input.addEventListener('input', () => {
      const v = input.value;
      let s = 0;
      if (v.length >= 8) s++;
      if (v.length >= 12) s++;
      if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
      if (/\d/.test(v)) s++;
      if (/[^A-Za-z0-9]/.test(v)) s++;
      bar.style.width = `${(s / 5) * 100}%`;
      bar.style.backgroundColor = s <= 1 ? '#c9643f' : s <= 3 ? '#b5a12e' : '#52713b';
    });
  });

  // Hitung karakter textarea
  $$('[data-char-count]').forEach((counter) => {
    const max = Number(counter.dataset.charCount);
    const field = counter.closest('.field').querySelector('textarea');
    const update = () => { counter.textContent = `${field.value.length}/${max}`; };
    field.addEventListener('input', update);
    update();
  });

  // Google login belum aktif → beri info
  $$('[data-google]').forEach((b) => b.addEventListener('click', () => toast('Google sign-in is coming soon. Use your email for now.')));
  $$('[data-forgot]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); toast('Email hello@portofiles.art and we’ll help you reset it.'); }));

  // Validasi ringan sebelum submit login/daftar (efek getar)
  $$('[data-auth-form]').forEach((form) => form.addEventListener('submit', (e) => {
    const bad = $$('input[required]', form).find((i) => !i.value.trim() || (i.type === 'email' && !/^\S+@\S+\.\S+$/.test(i.value)));
    if (bad) {
      e.preventDefault();
      const field = bad.closest('.field');
      field.classList.add('has-error');
      field.animate([{ transform: 'translateX(0)' }, { transform: 'translateX(-6px)' }, { transform: 'translateX(6px)' }, { transform: 'translateX(0)' }], { duration: 350 });
      bad.focus();
      setTimeout(() => $$('.btn.is-loading', form).forEach((b) => b.classList.remove('is-loading')), 0);
      try { sessionStorage.removeItem('pf-nav'); } catch (err) {}
    }
  }, true));
  $$('.field input, .field textarea').forEach((i) => i.addEventListener('input', () => i.closest('.field').classList.remove('has-error')));

  // Upload: drag & drop + preview
  const dz = $('[data-dropzone]');
  if (dz) {
    const input = $('[data-dropzone-input]', dz);
    const preview = $('[data-dropzone-preview]', dz);
    const showFile = (file) => {
      if (!file || !file.type.startsWith('image/')) { toast('Please choose an image file.', 'error'); return; }
      const url = URL.createObjectURL(file);
      dz.classList.remove('has-image', 'has-error');
      void dz.offsetWidth;
      preview.src = url;
      preview.onload = () => dz.classList.add('has-image');
    };
    input.addEventListener('change', () => showFile(input.files[0]));
    ['dragenter', 'dragover'].forEach((ev) => dz.addEventListener(ev, (e) => { e.preventDefault(); dz.classList.add('is-dragging'); }));
    ['dragleave', 'drop'].forEach((ev) => dz.addEventListener(ev, (e) => { e.preventDefault(); dz.classList.remove('is-dragging'); }));
    dz.addEventListener('drop', (e) => {
      const file = e.dataTransfer.files[0];
      if (!file) return;
      const dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;
      showFile(file);
    });
    $('[data-dropzone-change]', dz).addEventListener('click', () => input.click());
  }

  /* ---------------- 14. Artists: gambar mengikuti kursor ---------------- */
  const reveal = $('[data-hover-reveal]');
  if (reveal && finePointer && !calm) {
    const img = $('img', reveal);
    let x = 0, y = 0, cx = 0, cy = 0, vx = 0, active = false;
    $$('[data-hover-image]').forEach((row) => {
      row.addEventListener('mouseenter', () => { img.src = row.dataset.hoverImage; reveal.classList.add('is-visible'); active = true; reveal.style.scale = '1'; });
      row.addEventListener('mouseleave', () => { reveal.classList.remove('is-visible'); active = false; reveal.style.scale = ''; });
    });
    addEventListener('mousemove', (e) => { x = e.clientX; y = e.clientY; }, { passive: true });
    (function loop() {
      const nx = lerp(cx, x, 0.12);
      vx = nx - cx; cx = nx; cy = lerp(cy, y, 0.12);
      reveal.style.transform = `translate(${cx + 40}px, ${cy - 90}px) rotate(${clamp(vx * 0.6, -12, 12)}deg)`;
      requestAnimationFrame(loop);
    })();
  }
})();
