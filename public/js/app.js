/* Interaksi ringan tanpa build step. Aturan bisnis tetap di server. @2026-Alifiandra */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const csrf = () => $('meta[name="csrf-token"]')?.content || '';
  const storage = {
    get(k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set(k, v) { try { localStorage.setItem(k, v); } catch (e) { /* abaikan */ } },
  };

  /* Toast ------------------------------------------------------------- */
  $$('[data-toast]').forEach((t) => {
    const close = () => t.remove();
    $('[data-dismiss]', t)?.addEventListener('click', close);
    setTimeout(close, 6000);
  });

  /* Cegah submit ganda + loading state -------------------------------- */
  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.matches('[data-fav-form]') || form.method === 'dialog') return;
    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) { e.preventDefault(); return; }
    if (form.dataset.submitting) { e.preventDefault(); return; }
    form.dataset.submitting = '1';
    const btn = e.submitter || $('button[type="submit"], button:not([type])', form);
    if (btn) btn.classList.add('is-loading');
    setTimeout(() => { delete form.dataset.submitting; btn?.classList.remove('is-loading'); }, 8000);
  });

  /* Favorit tanpa reload; fallback ke submit biasa -------------------- */
  document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!form.matches('[data-fav-form]')) return;
    e.preventDefault();
    const btn = $('button', form);
    try {
      const res = await fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, credentials: 'same-origin' });
      if (!res.ok) throw new Error('gagal');
      const data = await res.json();
      btn.setAttribute('aria-pressed', data.favorite ? 'true' : 'false');
      btn.setAttribute('aria-label', data.favorite ? 'Hapus dari favorit' : 'Simpan ke favorit');
      const path = $('path', btn);
      if (path) path.setAttribute('d', data.favorite ? HEART_FILL : HEART);
    } catch (err) { form.submit(); }
  });
  const HEART_FILL = 'M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z';
  const HEART = 'M16.5 3c-1.74 0-3.41.81-4.5 2.09C10.91 3.81 9.24 3 7.5 3 4.42 3 2 5.42 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.32C18.6 15.36 22 12.28 22 8.5 22 5.42 19.58 3 16.5 3zm-4.4 15.55l-.1.1-.1-.1C7.14 14.24 4 11.39 4 8.5 4 6.5 5.5 5 7.5 5c1.54 0 3.04.99 3.57 2.36h1.87C13.46 5.99 14.96 5 16.5 5c2 0 3.5 1.5 3.5 3.5 0 2.89-3.14 5.74-7.9 10.05z';

  /* Bottom sheet filter (mobile) & sidebar panel ---------------------- */
  function bindToggle(openSel, panelSel, backdropSel) {
    const panel = $(panelSel); const backdrop = $(backdropSel);
    if (!panel) return;
    const open = () => { panel.classList.add('is-open'); backdrop?.classList.add('is-open'); panel.setAttribute('aria-hidden', 'false'); $('input, select, button, a', panel)?.focus(); };
    const close = () => { panel.classList.remove('is-open'); backdrop?.classList.remove('is-open'); panel.setAttribute('aria-hidden', 'true'); };
    $$(openSel).forEach((b) => b.addEventListener('click', open));
    $$('[data-close]', panel).forEach((b) => b.addEventListener('click', close));
    backdrop?.addEventListener('click', close);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
  }
  bindToggle('[data-open-filter]', '#filter-sheet', '#filter-backdrop');
  bindToggle('[data-open-menu]', '#sidebar', '#sidebar-backdrop');

  /* Galeri detail ----------------------------------------------------- */
  $$('[data-gallery]').forEach((g) => {
    const track = $('.gallery-track', g); const count = $('.gallery-count', g);
    const thumbs = $$('[data-thumb]', g.parentElement);
    const total = $$('figure', track).length;
    const update = () => {
      const i = Math.round(track.scrollLeft / track.clientWidth);
      if (count) count.textContent = `${i + 1}/${total}`;
      thumbs.forEach((t, j) => t.setAttribute('aria-current', j === i ? 'true' : 'false'));
    };
    track.addEventListener('scroll', () => requestAnimationFrame(update), { passive: true });
    thumbs.forEach((t, j) => t.addEventListener('click', () => track.scrollTo({ left: j * track.clientWidth, behavior: 'smooth' })));
  });

  /* Bagikan ----------------------------------------------------------- */
  $$('[data-share]').forEach((b) => b.addEventListener('click', async () => {
    const data = { title: document.title, url: location.href };
    if (navigator.share) { try { await navigator.share(data); } catch (e) { /* dibatalkan */ } return; }
    try { await navigator.clipboard.writeText(location.href); b.textContent = 'Tautan disalin'; } catch (e) { window.prompt('Salin tautan:', location.href); }
  }));

  /* Preview berkas sebelum unggah (VehicleMediaUploader) -------------- */
  $$('input[type="file"][data-preview]').forEach((input) => {
    input.addEventListener('change', () => {
      const box = document.getElementById(input.dataset.preview);
      if (!box) return;
      box.innerHTML = '';
      const file = input.files && input.files[0];
      if (!file) return;
      const maxKb = Number(input.dataset.maxKb || 5120);
      if (file.size > maxKb * 1024) {
        box.innerHTML = `<span class="error-text">Ukuran berkas melebihi ${Math.round(maxKb / 1024)} MB.</span>`;
        input.value = ''; return;
      }
      const allowPdf = input.dataset.allowPdf === '1';
      if (allowPdf && file.type === 'application/pdf') {
        const cap = document.createElement('div');
        cap.className = 'small muted'; cap.textContent = `PDF: ${file.name} · ${(file.size / 1024).toFixed(0)} KB — belum tersimpan`;
        box.append(cap); return;
      }
      if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
        box.innerHTML = `<span class="error-text">Format harus JPG, PNG, atau WebP${allowPdf ? ', atau PDF' : ''}.</span>`;
        input.value = ''; return;
      }
      const img = document.createElement('img');
      img.alt = 'Pratinjau ' + file.name;
      img.src = URL.createObjectURL(file);
      img.onload = () => URL.revokeObjectURL(img.src);
      const cap = document.createElement('div');
      cap.className = 'small muted'; cap.textContent = `${file.name} · ${(file.size / 1024).toFixed(0)} KB — belum tersimpan`;
      box.append(img, cap);
    });
  });


  /* Kamera (selfie/KTP) — hanya di konteks aman (HTTPS/localhost); fallback ke pilih berkas */
  const camDialog = $('#camera-dialog');
  if (camDialog && navigator.mediaDevices?.getUserMedia && window.isSecureContext) {
    const video = $('#camera-video', camDialog);
    const errBox = $('[data-camera-error]', camDialog);
    let stream = null; let target = null; let facing = 'user';
    const stop = () => { stream?.getTracks().forEach((t) => t.stop()); stream = null; };
    $$('[data-camera-for]').forEach((btn) => {
      btn.hidden = false;
      btn.addEventListener('click', async () => {
        target = document.getElementById(btn.dataset.cameraFor);
        facing = btn.dataset.facing || 'user';
        errBox.hidden = true;
        video.style.transform = facing === 'user' ? 'scaleX(-1)' : '';
        try {
          stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: facing, width: { ideal: 1280 } }, audio: false });
          video.srcObject = stream;
          camDialog.showModal();
        } catch (e) {
          window.alert('Kamera tidak dapat dibuka. Izinkan akses kamera atau pilih berkas foto.');
        }
      });
    });
    $('[data-camera-cancel]', camDialog).addEventListener('click', () => { stop(); camDialog.close(); });
    camDialog.addEventListener('close', stop);
    $('[data-camera-shoot]', camDialog).addEventListener('click', () => {
      if (!video.videoWidth || !target) return;
      const canvas = document.createElement('canvas');
      canvas.width = video.videoWidth; canvas.height = video.videoHeight;
      const ctx = canvas.getContext('2d');
      if (facing === 'user') { ctx.translate(canvas.width, 0); ctx.scale(-1, 1); }
      ctx.drawImage(video, 0, 0);
      canvas.toBlob((blob) => {
        if (!blob) { errBox.textContent = 'Gagal mengambil foto, coba lagi.'; errBox.hidden = false; return; }
        const file = new File([blob], `${target.name}-${Date.now()}.jpg`, { type: 'image/jpeg' });
        const dt = new DataTransfer(); dt.items.add(file);
        target.files = dt.files;
        target.dispatchEvent(new Event('change', { bubbles: true }));
        stop(); camDialog.close();
      }, 'image/jpeg', 0.9);
    });
  }

  /* Simulasi kredit (estimasi) --------------------------------------- */
  const credit = $('[data-credit]');
  if (credit) {
    const fmt = (n) => 'Rp' + Math.round(n).toLocaleString('id-ID');
    const calc = () => {
      const price = Number($('[name="price"]', credit).value || 0);
      const dpPct = Number($('[name="dp"]', credit).value || 0) / 100;
      const tenor = Number($('[name="tenor"]', credit).value || 12);
      const rate = Number($('[name="rate"]', credit).value || 0) / 100;
      const admin = Number($('[name="admin"]', credit).value || 0);
      const dp = price * dpPct; const principal = Math.max(price - dp, 0);
      const interest = principal * rate * (tenor / 12); // bunga flat — asumsi yang ditampilkan
      const monthly = tenor ? (principal + interest) / tenor : 0;
      $('[data-out="dp"]', credit).textContent = fmt(dp);
      $('[data-out="principal"]', credit).textContent = fmt(principal);
      $('[data-out="monthly"]', credit).textContent = fmt(monthly);
      $('[data-out="total"]', credit).textContent = fmt(dp + principal + interest + admin);
      $('[data-out="dpLabel"]', credit).textContent = Math.round(dpPct * 100) + '%';
    };
    credit.addEventListener('input', calc); calc();
  }

  /* Preview tema langsung --------------------------------------------- */
  const themeForm = $('[data-theme-form]');
  if (themeForm) {
    const prev = $('[data-theme-preview]');
    const lum = (hex) => { const c = hex.replace('#', '').match(/../g).map((x) => parseInt(x, 16) / 255).map((v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4)); return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2]; };
    const contrast = (a, b) => { const [x, y] = [lum(a), lum(b)].sort((m, n) => n - m); return (x + 0.05) / (y + 0.05); };
    const apply = () => {
      const p = $('[name="primary"]', themeForm).value; const a = $('[name="accent"]', themeForm).value;
      prev.style.setProperty('--tp-primary', p); prev.style.setProperty('--tp-accent', a);
      const ok = contrast(p, '#ffffff') >= 4.5;
      prev.style.setProperty('--tp-on', ok ? '#ffffff' : '#111827');
      const warn = $('[data-contrast-warning]'); if (warn) warn.hidden = ok;
    };
    $$('[data-preset]', themeForm).forEach((r) => r.addEventListener('change', () => {
      $('[name="primary"]', themeForm).value = r.dataset.primary; $('[name="accent"]', themeForm).value = r.dataset.accent; apply();
    }));
    themeForm.addEventListener('input', apply); apply();
  }

  /* Status online/offline --------------------------------------------- */
  const setOnline = () => document.body.classList.toggle('is-offline', !navigator.onLine);
  window.addEventListener('online', setOnline); window.addEventListener('offline', setOnline); setOnline();

  /* PWA: service worker & tombol instalasi (Blueprint §22) ------------ */
  if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
  }
  const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  const dismissedAt = Number(storage.get('installDismissedAt') || 0);
  const recentlyDismissed = Date.now() - dismissedAt < 14 * 24 * 3600 * 1000;
  let deferredPrompt = null;
  const triggers = $$('[data-install-trigger]');
  const banner = $('[data-install-banner]');
  const ua = navigator.userAgent;
  const isIOS = /iphone|ipad|ipod/i.test(ua);
  const showTriggers = () => {
    if (standalone) return;
    triggers.forEach((t) => { t.hidden = false; });
    if (banner && !recentlyDismissed) banner.classList.add('is-visible');
  };
  window.addEventListener('beforeinstallprompt', (e) => { e.preventDefault(); deferredPrompt = e; showTriggers(); });
  // Browser tanpa beforeinstallprompt (mis. Safari iOS): tampilkan panduan manual, bukan tombol palsu.
  if (!standalone && isIOS) showTriggers();
  window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    triggers.forEach((t) => { t.hidden = true; });
    banner?.classList.remove('is-visible');
  });
  const openGuide = () => {
    const dlg = $('#install-help'); if (!dlg) return;
    const kind = isIOS ? 'ios' : /android/i.test(ua) ? 'android' : 'desktop';
    $$('[data-guide]', dlg).forEach((g) => { g.hidden = g.dataset.guide !== kind; });
    dlg.showModal ? dlg.showModal() : dlg.setAttribute('open', '');
  };
  const install = async () => {
    if (deferredPrompt) {
      deferredPrompt.prompt();
      const choice = await deferredPrompt.userChoice;
      deferredPrompt = null;
      if (choice.outcome !== 'accepted') storage.set('installDismissedAt', String(Date.now()));
      return;
    }
    openGuide();
  };
  triggers.forEach((t) => t.addEventListener('click', install));
  $$('[data-install-later]').forEach((b) => b.addEventListener('click', () => {
    storage.set('installDismissedAt', String(Date.now()));
    banner?.classList.remove('is-visible');
  }));
})();
