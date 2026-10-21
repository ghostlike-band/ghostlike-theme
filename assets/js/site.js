/* GHOSTLIKE — Hero-Slider, Navigation, Scroll-Animationen, Kontaktformular */
(function () {
  'use strict';
  document.documentElement.classList.add('js');
  const cfg = window.GL || {};
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => [...el.querySelectorAll(s)];

  /* ================= HERO-SLIDER ================= */
  const hero = $('.hero');
  if (hero) {
    const slides = $$('.slide', hero);
    const dots = $$('.dots button', hero);
    const pauseBtn = $('[data-pause]', hero);
    const DAUER = Math.max(3, +cfg.interval || 7) * 1000;
    let cur = 0, t0 = performance.now(), elapsed = 0, paused = reduced, hover = false, visible = true;
    hero.style.setProperty('--dauer', DAUER / 1000 + 's');

    // Handy: eigenes Hochformat-Video/-Vorschaubild, falls hinterlegt. Videos laden erst bei Bedarf.
    const mobileQ = matchMedia('(max-width: 767px)');
    const ensureSrc = (v) => {
      const want = (mobileQ.matches && v.dataset.srcMobile) || v.dataset.src;
      if (want && v.dataset.loaded !== want) { v.src = want; v.dataset.loaded = want; }
    };
    const setPosters = () => $$('video', hero).forEach((v) => {
      if (!v.dataset.posterDesktop) v.dataset.posterDesktop = v.getAttribute('poster') || '';
      const p = (mobileQ.matches && v.dataset.posterMobile) || v.dataset.posterDesktop;
      if (p) v.poster = p;
    });
    setPosters();
    const playVideo = (slide) => {
      const v = $('video', slide);
      if (!v) return;
      ensureSrc(v);
      try { v.currentTime = 0; } catch (e) {}
      const p = v.play(); if (p) p.catch(() => {});
    };
    const prefetchNext = () => {
      const nv = slides.length > 1 && $('video', slides[(cur + 1) % slides.length]);
      if (nv) { nv.preload = 'auto'; ensureSrc(nv); }
    };
    mobileQ.addEventListener && mobileQ.addEventListener('change', () => { setPosters(); playVideo(slides[cur]); });

    function go(i) {
      if (slides.length < 2) return;
      const prev = slides[cur];
      cur = (i + slides.length) % slides.length;
      slides.forEach((s, n) => s.classList.toggle('is-active', n === cur));
      dots.forEach((d, n) => { d.setAttribute('aria-selected', n === cur); d.style.setProperty('--p', n < cur ? 1 : 0); });
      const pv = $('video', prev); if (pv && prev !== slides[cur]) pv.pause();
      playVideo(slides[cur]);
      prefetchNext();
      elapsed = 0; t0 = performance.now();
    }
    function tick(now) {
      if (!paused && !hover && visible && !document.hidden) elapsed += now - t0;
      t0 = now;
      const p = Math.min(1, elapsed / DAUER);
      if (dots[cur]) dots[cur].style.setProperty('--p', p);
      if (p >= 1) go(cur + 1);
      requestAnimationFrame(tick);
    }

    playVideo(slides[0]);
    // Nächstes Video erst holen, wenn die Seite fertig geladen ist
    addEventListener('load', () => setTimeout(prefetchNext, 1500));
    if (slides.length > 1) {
      dots.forEach((d, i) => d.addEventListener('click', () => go(i)));
      $$('.arrow[data-dir]', hero).forEach((b) => b.addEventListener('click', () => go(cur + +b.dataset.dir)));
      const setPaused = (v) => {
        paused = v;
        if (!pauseBtn) return;
        pauseBtn.textContent = paused ? '▶' : '❚❚';
        pauseBtn.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
      };
      if (pauseBtn) pauseBtn.addEventListener('click', () => setPaused(!paused));
      setPaused(paused);
      if (matchMedia('(hover: hover)').matches) {
        $('.slides', hero).addEventListener('mouseenter', () => (hover = true));
        $('.slides', hero).addEventListener('mouseleave', () => (hover = false));
      }
      new IntersectionObserver(([e]) => { visible = e.isIntersecting; }, { threshold: 0.2 }).observe(hero);
      hero.addEventListener('keydown', (e) => { if (e.key === 'ArrowRight') go(cur + 1); if (e.key === 'ArrowLeft') go(cur - 1); });
      let sx = null;
      hero.addEventListener('touchstart', (e) => { sx = e.touches[0].clientX; }, { passive: true });
      hero.addEventListener('touchend', (e) => {
        if (sx === null) return;
        const dx = e.changedTouches[0].clientX - sx; sx = null;
        if (Math.abs(dx) > 50) go(cur + (dx < 0 ? 1 : -1));
      });
      requestAnimationFrame(tick);
    }
  }

  /* ================= SCROLL-ANIMATIONEN ================= */
  $$('[data-split]').forEach((el) => {
    const lines = el.innerHTML.split(/<br\s*\/?>/i);
    el.innerHTML = lines.map((l, i) => `<span class="split-line" style="--i:${i}"><span>${l.trim()}</span></span>`).join('');
  });
  $$('[data-stagger]').forEach((el) => [...el.children].forEach((c, i) => c.style.setProperty('--i', i)));

  const revealables = $$('[data-reveal],[data-stagger],[data-split]');
  // Was beim Laden schon sichtbar ist, sofort zeigen (nicht auf den Observer warten).
  const inView = (el) => { const r = el.getBoundingClientRect(); return r.top < innerHeight && r.bottom > 0; };
  const revealVisible = () => revealables.forEach((el) => { if (!el.classList.contains('is-in') && inView(el)) el.classList.add('is-in'); });
  requestAnimationFrame(revealVisible);
  addEventListener('load', revealVisible);
  addEventListener('pageshow', revealVisible);
  setTimeout(revealVisible, 1200);
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((e) => {
        if (!e.isIntersecting) return;
        e.target.classList.add('is-in');
        io.unobserve(e.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -5% 0px' });
    revealables.forEach((el) => io.observe(el));
  } else {
    revealables.forEach((el) => el.classList.add('is-in'));
  }

  const par = $$('[data-parallax]');
  function parallax() {
    const vh = innerHeight;
    par.forEach((el) => {
      const r = el.parentElement.getBoundingClientRect();
      if (r.bottom < 0 || r.top > vh) return;
      el.style.transform = `translate3d(0, ${((r.top + r.height / 2 - vh / 2) * -parseFloat(el.dataset.parallax)).toFixed(1)}px, 0)`;
    });
  }

  /* ================= NAVIGATION ================= */
  const nav = $('#nav'), burger = $('.nav__burger'), menu = $('#menu');
  const solidAlways = nav && nav.classList.contains('is-solid');
  let lastY = scrollY, ticking = false;
  function onScroll() {
    const y = scrollY;
    if (nav) {
      if (!solidAlways) nav.classList.toggle('is-solid', y > innerHeight * 0.6);
      // Menü bleibt beim Scrollen immer oben sichtbar (kein Ausblenden mehr)
    }
    lastY = y;
    if (!reduced) parallax();
    revealVisible();
    ticking = false;
  }
  addEventListener('scroll', () => { if (!ticking) { requestAnimationFrame(onScroll); ticking = true; } }, { passive: true });

  const links = $$('.nav__links a');
  const ids = links.map((a) => (a.hash || '').slice(1)).filter(Boolean);
  if (ids.length && 'IntersectionObserver' in window) {
    const secIO = new IntersectionObserver((entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) links.forEach((a) => a.classList.toggle('is-active', a.hash === '#' + e.target.id));
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    ids.forEach((id) => { const s = document.getElementById(id); if (s) secIO.observe(s); });
  }

  if (burger && menu) {
    const setMenu = (open) => {
      burger.setAttribute('aria-expanded', open);
      menu.hidden = !open;
      menu.classList.toggle('is-open', open);
      document.body.style.overflow = open ? 'hidden' : '';
    };
    burger.addEventListener('click', () => setMenu(menu.hidden));
    $$('a', menu).forEach((a) => a.addEventListener('click', () => setMenu(false)));
    addEventListener('keydown', (e) => { if (e.key === 'Escape' && !menu.hidden) setMenu(false); });
  }

  /* ================= KONTAKTFORMULAR ================= */
  const form = $('#contact-form');
  if (form && cfg.contactUrl) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const status = $('.form__status', form);
      const btn = $('.form__submit', form);
      if (!form.checkValidity()) { status.textContent = 'Please fill in all fields ↑'; form.reportValidity(); return; }
      const data = Object.fromEntries(new FormData(form).entries());
      data.t = form.dataset.start || '';
      btn.disabled = true; status.textContent = 'Sending…';
      try {
        const res = await fetch(cfg.contactUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        const json = await res.json().catch(() => ({}));
        if (res.ok && json.ok) { status.textContent = 'Thanks! Your message is on its way. We’ll get back to you soon.'; form.reset(); }
        else status.textContent = json.message || 'Something went wrong. Please try again.';
      } catch (err) {
        status.textContent = 'No connection. Please try again.';
      }
      btn.disabled = false;
    });
  }

  onScroll();
})();

/* YouTube erst nach Klick laden (Datenschutz) */
document.addEventListener('click', (e) => {
  const b = e.target.closest('.yt-consent__play');
  if (!b) return;
  const box = b.closest('.yt-consent');
  const f = document.createElement('iframe');
  f.src = box.dataset.yt;
  f.title = box.dataset.title || 'YouTube video';
  f.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
  f.allowFullscreen = true;
  f.referrerPolicy = 'strict-origin-when-cross-origin';
  box.replaceChildren(f);
  box.classList.add('is-on');
});
