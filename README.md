# Marketplace Jual Beli Kendaraan — Dealer SaaS + PWA

Marketplace motor & mobil baru/seken yang terintegrasi dengan dashboard dealer multi-tenant, panel admin platform, REST API `/api/v1` untuk Flutter, dan Progressive Web App. Dibangun mengikuti **Blueprint Produk v1.0** dan **Blueprint UI/UX v1.0** (17–18 Oktober 2026).

> Nama merek sementara **MotoMarket** (Blueprint UI §3). Ganti lewat `MARKETPLACE_BRAND` di `.env` tanpa mengubah kode.
> Developer: `@2026-Alifiandra`

---

## Isi rilis ini (cakupan MVP — Blueprint §9)

| Area | Yang sudah ada |
|---|---|
| **Web publik (SSR, SEO)** | Beranda, katalog + filter (kategori motor/mobil, kondisi, merek, harga, tahun, km, transmisi, kota, jenis penjual) dengan bottom sheet di HP & sidebar di desktop, detail iklan (galeri, spesifikasi, structured data, iklan serupa), direktori & profil dealer (dengan warna merek tenant), simulasi kredit (berlabel estimasi), tukar tambah, panduan, bantuan, halaman legal, sitemap.xml, robots.txt |
| **Akun** | Daftar/masuk, profil, mode tampilan terang/gelap/sistem, favorit, iklan saya, minat & jadwal |
| **Penjual perorangan** | Pasang iklan (draf → review → tayang), batas iklan aktif, tandai terjual/arsip |
| **Galeri bukti per kategori** | BPKB, STNK, nomor rangka, nomor mesin (privat), tampak depan/belakang/kanan/kiri, orang + unit — preview sebelum simpan, status verifikasi, izin publikasi terpisah, private storage + akses teraudit |
| **Dashboard dealer (tenant)** | Onboarding dealer + paket Gratis, ringkasan & checklist, widget **cuaca + lokasi cabang**, inventaris unit (status stok tervalidasi, unit titipan/konsinyasi), kartu **HPP & laba per unit** (estimasi vs aktual, reversal teraudit, idempotency), iklan + kuota paket, leads/CRM (pipeline, penugasan sales, follow-up), booking/test ride, anggota & peran, cabang, **Pusat Pengaturan Tema** (preset, preview, validasi kontras), log audit |
| **Admin platform** | Dashboard + estimasi MRR, moderasi iklan (setujui/tolak beralasan), laporan iklan, tenant (suspend/aktifkan/verifikasi beralasan), pengguna, master katalog, paket & limit (ubah tanpa deploy), log audit |
| **REST API v1** | Envelope `data/meta/errors/request_id`, Sanctum token untuk Flutter, listing/brand/model/lokasi/dealer, lead, favorit, booking, inventaris & leads dealer (tenant-scoped), langganan |
| **PWA** | Manifest, ikon (termasuk maskable), service worker (cache aset + fallback offline; **tidak pernah** meng-cache akun/dealer/admin/API/dokumen), tombol **Instal Aplikasi** di header, footer & kartu beranda + panduan manual untuk browser tanpa prompt |
| **Keamanan** | Tenant isolation (404 untuk ID tenant lain), RBAC per peran tenant, nomor rangka/mesin terenkripsi + hash, rate limit login/lead/laporan/API, audit log, validasi MIME & ukuran di server |

**35 feature test / 234 assertion lulus**, termasuk uji akses silang tenant, dokumen privat, moderasi, kuota paket, transisi status, reversal biaya, dan envelope API.

### Ditunda (sesuai Blueprint §9 "Ditunda setelah MVP")
Payment gateway & invoice otomatis, chat internal (MVP memakai WhatsApp deep link + pencatatan consent), modul leasing/trade-in/konsinyasi lengkap (struktur unit titipan & HPP sudah siap), OCR KTP & data nasabah, custom domain white-label, web push notification, aplikasi Flutter.

---

## Kebutuhan

- PHP **8.3+** (blueprint merekomendasikan 8.4) dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `gd`, `fileinfo`, `intl`
- Composer 2
- MySQL 8 / MariaDB 10.6+ (Laragon sudah menyertakan)
- **Tidak perlu Node/npm** — CSS & JS sudah siap pakai di `public/` (lihat catatan desain di bawah)

## Instalasi di Laragon (PowerShell)

```powershell
# 1. Ambil kode
cd C:\laragon\www
git clone https://github.com/setiawan30y-creator/jual-beli-kendaraaan.git
cd jual-beli-kendaraaan

# 2. Dependensi PHP
composer install

# 3. Konfigurasi
Copy-Item .env.example .env
php artisan key:generate

# 4. Buat database (ubah user/password bila MySQL Anda memakai password)
& "C:\laragon\bin\mysql\$((Get-ChildItem C:\laragon\bin\mysql)[0].Name)\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS jual_beli_kendaraan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Tabel, data master & data demo, symlink storage
php artisan migrate --seed
php artisan storage:link
```

Buka `http://jual-beli-kendaraaan.test` (auto virtual host Laragon — klik **Reload** di Laragon bila belum muncul) atau jalankan `php artisan serve` lalu buka `http://127.0.0.1:8000`.

> PWA (service worker & tombol instal) hanya aktif di HTTPS atau `localhost`. Di Laragon, aktifkan SSL lewat menu **Laragon → Apache/Nginx → SSL → Enabled**, lalu ubah `APP_URL` ke `https://...`.

### Akun demo (kata sandi semua: `password123`)

| Email | Peran |
|---|---|
| `admin@example.com` | Super admin platform |
| `moderator@example.com` | Moderator iklan |
| `dealer@example.com` | Pemilik "Dealer Motor Contoh" (paket Pro) |
| `sales@example.com` | Sales di dealer yang sama (hanya lead yang ditugaskan) |
| `showroom@example.com` | Pemilik "Showroom Mobil Contoh" (paket Starter, tema hijau) |
| `penjual@example.com` | Penjual perorangan (1 iklan tayang, 1 menunggu moderasi) |
| `pembeli@example.com` | Pembeli |

Data demo tidak dibuat bila `APP_ENV=production`. Untuk produksi cukup `php artisan migrate --force` lalu `php artisan db:seed --class=ReferenceDataSeeder --force`.

### Menjalankan test

```powershell
php artisan test
```

(Test memakai SQLite in-memory; tidak menyentuh database MySQL Anda.)

---

## Struktur penting

```
app/
  Http/Controllers/Public    web publik (beranda, katalog, detail, dealer, jual)
  Http/Controllers/Dealer    dashboard dealer (tenant-scoped)
  Http/Controllers/Admin     panel admin platform
  Http/Controllers/Api/V1    REST API untuk Flutter
  Http/Middleware            ResolveTenant, EnsurePlatformStaff, AssignRequestId, ...
  Services                   ListingService (state machine), ListingSearch, MediaService,
                             LeadService, PlanLimits (kuota paket), WeatherService
  Support                    TenantContext, Audit, ApiResponse, Color (kontras), Format
config/marketplace.php       merek, batas unggah, preset tema, matriks izin peran tenant, cuaca
public/css/app.css           design tokens (CSS variables) + komponen Material-inspired
public/js/app.js             interaksi ringan (filter sheet, galeri, favorit, preview unggah, instal PWA)
public/sw.js                 service worker
resources/views/components   listing-card, media-uploader, field, status-badge, icon, ...
```

### Aturan bisnis yang dijaga di server
- **Status iklan**: `draft → pending_review → published → reserved/sold/expired/archived`, `rejected` wajib alasan. Pengajuan butuh unit **Tersedia** + minimal satu foto publik + kuota paket.
- **Status unit**: transisi eksplisit (`VehicleUnit::TRANSITIONS`) dengan row lock; perubahan unit tersinkron ke iklan.
- **HPP**: entri biaya yang sudah diposting tidak dihapus — dibalik dengan entri negatif dan alasan. Estimasi dipisah dari aktual.
- **Tenant**: `tenant_id` tidak pernah diterima dari client; resource tenant lain menghasilkan 404.
- **Dokumen privat**: disimpan di `storage/app/private`, dilayani lewat route berotorisasi dengan header `private, no-store`, setiap akses dicatat.

## Catatan desain
- Blueprint menyebut Tailwind CSS. Versi ini memakai **CSS tokens tanpa build step** agar langsung jalan di Laragon tanpa npm; semua warna/radius/spacing berasal dari variabel CSS (`--primary`, `--surface`, dst.) sehingga mudah dipetakan ke `theme.extend` Tailwind bila nanti ingin migrasi.
- Widget cuaca memakai Open-Meteo (tanpa API key) berdasarkan koordinat cabang aktif; bila gagal, UI menampilkan "Cuaca tidak tersedia" — tidak mengarang data. Matikan dengan `WEATHER_ENABLED=false`.
- Halaman Kebijakan Privasi dan Syarat & Ketentuan masih **draf templat** — tinjau bersama penasihat hukum sebelum peluncuran.
- Harga paket (Rp0 / 149.000 / 399.000 / 799.000) adalah **hipotesis** dari blueprint dan dapat diubah di Admin → Paket & limit.

## Langkah berikutnya yang disarankan (Blueprint §8)
1. Tetapkan nama merek, domain, dan warna final → `.env` + preset tema.
2. Pilot dengan beberapa dealer; ukur search→detail→lead conversion.
3. Payment gateway & invoice langganan (adapter provider, webhook idempotent).
4. Modul nasabah (KTP/OCR), trade-in, konsinyasi, leasing.
5. Aplikasi Flutter memakai `/api/v1` (dokumentasi OpenAPI).
