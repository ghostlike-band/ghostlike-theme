/* GHOSTLIKE → Videos: Upload zu bunny.net (TUS, fortsetzbar), Liste, „als Header-Video verwenden“ */
(function () {
  'use strict';
  const C = window.GLB || {};
  const $ = (s, el = document) => el.querySelector(s);
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  async function ajax(action, data = {}) {
    const body = new URLSearchParams({ action, nonce: C.nonce, ...data });
    const res = await fetch(C.ajax, { method: 'POST', credentials: 'same-origin', body });
    const json = await res.json().catch(() => ({}));
    if (!json.success) throw new Error((json.data && json.data.message) || 'Fehler bei der Anfrage.');
    return json.data;
  }

  // Slides in die Auswahl
  const slideOpts = (C.slides || []).map((s) => `<option value="${s.id}">${esc(s.title)}</option>`).join('');
  $('#glb-slide').insertAdjacentHTML('beforeend', slideOpts);

  /* ================= UPLOAD ================= */
  const drop = $('#glb-drop'), input = $('#glb-file'), prog = $('#glb-progress'), bar = $('progress', prog), status = $('#glb-status');
  let uploading = false;

  $('#glb-pick').addEventListener('click', (e) => { e.stopPropagation(); input.click(); });
  drop.addEventListener('click', () => input.click());
  input.addEventListener('change', () => { if (input.files[0]) start(input.files[0]); });
  ['dragenter', 'dragover'].forEach((t) => drop.addEventListener(t, (e) => { e.preventDefault(); drop.classList.add('is-over'); }));
  ['dragleave', 'drop'].forEach((t) => drop.addEventListener(t, (e) => { e.preventDefault(); drop.classList.remove('is-over'); }));
  drop.addEventListener('drop', (e) => { const f = e.dataTransfer.files[0]; if (f) start(f); });
  window.addEventListener('beforeunload', (e) => { if (uploading) { e.preventDefault(); e.returnValue = ''; } });

  async function start(file) {
    if (uploading) return;
    if (!/^video\//.test(file.type) && !/\.(mp4|m4v|mov|webm)$/i.test(file.name)) { alert('Bitte eine Videodatei wählen (MP4).'); return; }
    const title = $('#glb-title').value.trim() || file.name.replace(/\.[^.]+$/, '');
    const slide = $('#glb-slide').value, target = $('#glb-target').value;
    uploading = true; prog.hidden = false; bar.value = 0;
    status.textContent = 'Video wird bei bunny.net angelegt …';
    try {
      const v = await ajax('gl_bunny_create', { title });
      await new Promise((resolve, reject) => {
        const up = new tus.Upload(file, {
          endpoint: 'https://video.bunnycdn.com/tusupload',
          retryDelays: [0, 3000, 5000, 10000, 20000, 60000],
          headers: { AuthorizationSignature: v.signature, AuthorizationExpire: String(v.expire), VideoId: v.guid, LibraryId: String(v.library) },
          metadata: { filetype: file.type, title },
          onError: reject,
          onProgress: (done, total) => {
            const p = Math.round((done / total) * 100);
            bar.value = p;
            status.textContent = `Lade hoch … ${p} % (${(done / 1048576).toFixed(1)} von ${(total / 1048576).toFixed(1)} MB) – Seite bitte offen lassen.`;
          },
          onSuccess: resolve,
        });
        up.findPreviousUploads().then((prev) => { if (prev.length) up.resumeFromPreviousUpload(prev[0]); up.start(); });
      });
      bar.value = 100;
      let msg = 'Hochgeladen ✓ bunny.net wandelt das Video jetzt um (meist 1–5 Minuten).';
      if (slide) {
        const a = await ajax('gl_bunny_assign', { slide, target, url: v.mp4_720 });
        msg += ' ' + a.message + ' Es erscheint auf der Website, sobald die Umwandlung fertig ist.';
      }
      status.textContent = msg;
      $('#glb-title').value = ''; input.value = '';
      load();
    } catch (err) {
      status.textContent = 'Upload fehlgeschlagen: ' + (err.message || err) + ' – einfach dieselbe Datei erneut wählen, der Upload setzt fort.';
    }
    uploading = false;
  }

  /* ================= LISTE ================= */
  const list = $('#glb-list');
  let pollTimer = null;
  const fmtLen = (s) => (s ? `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}` : '–');
  const fmtMB = (b) => (b ? (b / 1048576).toFixed(1) + ' MB' : '');

  function card(v) {
    const badge = v.failed ? 'is-fail' : v.done ? 'is-done' : '';
    const state = v.done || v.failed ? v.status : `${v.status} ${v.progress ? v.progress + ' %' : ''}`;
    const best = v.mp4.length ? v.mp4[0].url : v.mp4_720;
    const fmt = v.width && v.height ? (v.height > v.width ? 'Hochformat' : 'Querformat') + ` · ${v.width}×${v.height}` : '';
    return `<div class="gl-vid" data-guid="${esc(v.guid)}">
      <div class="gl-vid__img" style="${v.thumb ? `background-image:url('${esc(v.thumb)}')` : ''}"><span class="gl-vid__badge ${badge}">${esc(state)}</span></div>
      <div class="gl-vid__body">
        <strong>${esc(v.title)}</strong>
        <span class="description">${fmtLen(v.length)} · ${fmtMB(v.size)} ${fmt ? '· ' + fmt : ''}</span>
        <div class="gl-row"><code>${esc(best)}</code></div>
        <div class="gl-row">
          <button type="button" class="button button-small" data-copy="${esc(best)}">MP4-Link kopieren</button>
          <button type="button" class="button button-small" data-copy="${esc(v.hls)}">HLS-Link</button>
        </div>
        <div class="gl-row">
          <select class="glb-a-slide"><option value="">Header-Slide wählen …</option>${slideOpts}</select>
          <select class="glb-a-target"><option value="desktop">Desktop</option><option value="mobile">Handy</option></select>
          <button type="button" class="button button-small button-primary" data-assign="${esc(best)}">Eintragen</button>
        </div>
        <div class="gl-row"><button type="button" class="button-link" style="color:#b32d2e" data-del="${esc(v.guid)}">Löschen</button></div>
      </div></div>`;
  }

  async function load() {
    clearTimeout(pollTimer);
    try {
      const vids = await ajax('gl_bunny_list');
      list.innerHTML = vids.length ? vids.map(card).join('') : '<p>Noch keine Videos in der Library.</p>';
      if (vids.some((v) => !v.done && !v.failed)) pollTimer = setTimeout(load, 8000); // Umwandlung läuft → automatisch nachsehen
    } catch (err) {
      list.innerHTML = `<p style="color:#b32d2e">${esc(err.message)}</p>`;
    }
  }

  list.addEventListener('click', async (e) => {
    const c = e.target.closest('[data-copy]');
    if (c) {
      const label = c.textContent;
      try { await navigator.clipboard.writeText(c.dataset.copy); c.textContent = 'Kopiert ✓'; setTimeout(() => { c.textContent = label; }, 1800); }
      catch (err) { window.prompt('Link kopieren:', c.dataset.copy); }
      return;
    }
    const a = e.target.closest('[data-assign]');
    if (a) {
      const box = a.closest('.gl-vid');
      const slide = box.querySelector('.glb-a-slide').value, target = box.querySelector('.glb-a-target').value;
      if (!slide) { alert('Bitte zuerst eine Slide wählen.'); return; }
      try { const r = await ajax('gl_bunny_assign', { slide, target, url: a.dataset.assign }); a.textContent = 'Eingetragen ✓'; alert(r.message); }
      catch (err) { alert(err.message); }
      return;
    }
    const d = e.target.closest('[data-del]');
    if (d) {
      if (!confirm('Video bei bunny.net endgültig löschen? Slides, die es verwenden, zeigen dann nur noch das Bild.')) return;
      try { await ajax('gl_bunny_delete', { guid: d.dataset.del }); load(); }
      catch (err) { alert(err.message); }
    }
  });
  $('#glb-reload').addEventListener('click', load);

  load();
})();
