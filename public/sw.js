/* Service worker (Blueprint §22). @2026-Alifiandra
 * - Cache-first: aset statis (CSS/JS/ikon).
 * - Network-first + fallback: halaman publik GET.
 * - TIDAK pernah meng-cache: /api, /dasbor, /admin, /akun, /media (dokumen privat), /masuk, /daftar, request non-GET.
 */
const VERSION = 'v1-2026-10-18';
const STATIC_CACHE = `static-${VERSION}`;
const PAGE_CACHE = `pages-${VERSION}`;
const OFFLINE_URL = '/offline';
const PRECACHE = ['/css/app.css', '/js/app.js', '/icons/icon.svg', '/icons/icon-192.png', OFFLINE_URL];
const PRIVATE = [/^\/api\//, /^\/dasbor/, /^\/admin/, /^\/akun/, /^\/media\//, /^\/unit\//, /^\/masuk/, /^\/daftar/, /^\/keluar/, /^\/jual/];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(STATIC_CACHE).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => ![STATIC_CACHE, PAGE_CACHE].includes(k)).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;
  if (PRIVATE.some((re) => re.test(url.pathname))) return; // biarkan ke jaringan, jangan disimpan

  if (/\.(css|js|svg|png|webp|jpg|jpeg|woff2?)$/.test(url.pathname) && !url.pathname.startsWith('/storage/')) {
    event.respondWith(caches.match(req).then((hit) => hit || fetch(req).then((res) => {
      if (res.ok) caches.open(STATIC_CACHE).then((c) => c.put(req, res.clone()));
      return res;
    })));
    return;
  }

  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).then((res) => {
        // Simpan hanya halaman publik yang ditandai server aman di-cache (tamu, GET, rute publik).
        if (res.ok && res.headers.get('X-PWA-Cacheable') === '1') {
          const copy = res.clone();
          caches.open(PAGE_CACHE).then((c) => c.put(req, copy));
        }
        return res;
      }).catch(async () => (await caches.match(req)) || caches.match(OFFLINE_URL))
    );
  }
});
