# ♻️ Daurin — Marketplace Daur Ulang Tiga Lapis

> **Demo publik:** **https://wearegettingcooked.web.id**
>
> Stack: Laravel 13 · Inertia v3 · Vue 3 + TypeScript · Tailwind v4 · Fortify · Reverb (realtime) · Leaflet/OpenStreetMap · Klasifikasi foto berbasis AI

**Daurin** adalah aplikasi web marketplace daur ulang yang dibangun **PT Lestari Daur Nusantara (LDN)** untuk menyambung rantai yang selama ini terputus: **Rumah Tangga → Pengepul → Industri Pengolah**. Sampah rumah tangga dipilah & di-*listing* (dibantu klasifikasi jenis dari foto), diambil pengepul lewat **rute hemat biaya**, dipilah lagi menjadi **bahan baku**, lalu dipesan & dinegosiasikan harganya oleh industri hingga menjadi **bahan baku jadi**. Material mengalir berjenjang dan **dapat dilacak** dari hulu ke hilir — tidak ada fitur yang berdiri sendiri.

---

## 1. Fitur Wajib (8) — Peta ke Implementasi

| No. | Fitur (sesuai studycase) | Status | Lokasi implementasi |
| :-- | :-- | :--: | :-- |
| 1 | Autentikasi & RBAC (3 peran) | ✅ | Fortify + `app/Enums/UserRole.php`, middleware `app/Http/Middleware/EnsureRole.php` (alias `role:`), prefix route `rt/` · `pengepul/` · `industri/` |
| 2 | Pendaftaran + CRUD listing/data master (jenis sampah ditangani) | ✅ | Registrasi `resources/js/pages/auth/Register.vue`; CRUD listing `ListingSampahController`; jenis ditangani `PengepulJenisController` (`/pengepul/jenis`) |
| 3 | Peta titik pengambilan + rute (berbasis lokasi) | ✅ | `resources/js/pages/Pengepul/Ketersediaan.vue` (Leaflet + OSM) + algoritma `resources/js/lib/route.ts` |
| 4 | **Klasifikasi jenis sampah dari foto (AI/ML wajib)** | ✅ | `app/Http/Controllers/ClassifyController.php` (POST `/classify`) dipakai di `resources/js/pages/Rt/Create.vue` |
| 5 | Pilah & jual sampah → ambil → pilah jadi bahan baku | ✅ | `ListingSampah` → klaim/penawaran (`PenawaranController`) → `BahanBakuController` |
| 6 | Pemesanan + negosiasi harga + transaksi | ✅ | `PesananController`, `NegosiasiController`, `LelangController` (+ lelang realtime) |
| 7 | Marketplace landing (etalase 3 lapis) + dashboard ringkas | ✅ | `MarketplaceController@index` (`/`), `DashboardController` + `resources/js/pages/Dashboard.vue` |
| 8 | Deploy publik | ✅ | **https://wearegettingcooked.web.id** (lihat bagian Deployment) |

---

## 2. Alur Proses Bisnis (rantai 3-lapis)

```
RUMAH TANGGA  pilah & input sampah (klasifikasi foto bantu pastikan jenis)
      │        ListingSampah  [tersedia]
      ▼
MARKETPLACE   listing muncul di etalase publik
      │
      ▼
PENGEPUL      lihat ketersediaan TERFILTER jenis yang ia tangani → ambil via rute optimal
      │        PenawaranListing (tawar) → klaim   ListingSampah  [tersedia → diambil → terjual]
      ▼
PENGEPUL      pilah detail → input bahan baku (jenis, peruntukan, harga awal) → listing/lelang
      │        BahanBaku  [tersedia → dilelang → terjual]
      ▼
INDUSTRI      lihat bahan baku → pesan → negosiasi (tawar / tawar-balik) → deal
      │        Pesanan  [nego → deal → (dibayar)]   ·   Negosiasi (offer/counter-offer)
      ▼
INDUSTRI      olah jadi BAHAN BAKU JADI (opsional dijual kembali) → Dashboard volume/transaksi
               BahanJadi  [tersedia → terjual]
```

**Status berjenjang & traceable** — setiap material menyimpan asalnya: `BahanBaku.source_listing_id` → listing RT, `BahanJadi.source_pesanan_id` → pesanan/bahan baku asal.

---

## 3. Aktor & Hak Akses (RBAC)

| Aktor | Peran (`UserRole`) | Hak akses inti |
| :-- | :-- | :-- |
| **Rumah Tangga** | `rumah_tangga` | Daftar (koordinat), pilah & input sampah + klasifikasi foto, jual (listing), terima/tolak penawaran, lihat status |
| **Pengepul** | `pengepul` | Daftar + **jenis sampah ditangani**, lihat ketersediaan **terfilter** jenis & wilayah, klaim/tawar via **peta + rute**, pilah jadi bahan baku, jual/lelang |
| **Industri Pengolah** | `industri` | Lihat bahan baku, pesan, negosiasi harga (tawar/tawar-balik) + lelang, transaksi, olah jadi bahan baku jadi |

RBAC ditegakkan oleh middleware `EnsureRole` (`role:rumah_tangga`, `role:pengepul,industri`, dst.) pada setiap grup route. Registrasi memakai wizard 2 langkah — langkah kedua khusus **Industri** meminta `nama_pt` & `alamat_pt`; geolokasi (`lat`/`lng`) diambil dari browser saat daftar.

---

## 4. Komponen AI/ML (Wajib)

**Waste Image Classification (Computer Vision).** Saat RT mengunggah foto di `Rt/Create.vue`, foto dikirim ke `ClassifyController` (POST `/classify`) yang meneruskan ke model **SigLIP2** (Hugging Face Space, dikonfigurasi di `config/services.php` → `services.waste_classifier.url`, default `https://anggapuspa-apisampahplastik.hf.space`). Hasilnya berupa label, **confidence (0–1)**, emoji & tip, lalu otomatis mengisi field `jenis_sampah` dan disimpan ke `ListingSampah.ai_label` + `ai_confidence`.

Sesuai ketentuan studycase (bagian H): memakai **model pra-terlatih** dibolehkan dan akurasi sempurna **bukan** syarat — yang dinilai keberadaan & kewajaran alurnya. Bila API tidak tersedia, sistem **fallback** ke klasifikasi mock berbasis nama file sehingga alur tetap bisa didemokan offline.

**Bonus algoritmik — optimasi rute** (`resources/js/lib/route.ts`): heuristik **nearest-neighbor** + jarak **haversine**, faktor jalan 1.4×, batasan kapasitas berat, **estimasi jarak, waktu, dan biaya** (≈Rp 3.000/km, 40 km/jam), serta integrasi membuka rute di Google Maps.

---

## 5. Teknologi

**Backend**
- PHP 8.3+ (target produksi 8.4) · Laravel 13
- Laravel Fortify (autentikasi headless) · Wayfinder (typed routes)
- Laravel Reverb + Laravel Echo + Pusher-js (lelang/nego realtime)
- Database: SQLite (lokal, default) / MySQL · MariaDB (produksi)

**Frontend**
- Inertia v3 + Vue 3 (Composition API, TypeScript)
- Vite 8 · Tailwind CSS v4
- shadcn/vue (reka-ui) · Lucide icons · vue-sonner (toast)
- Leaflet 1.9.4 + OpenStreetMap (peta & rute)

---

## 6. Cara Menjalankan (Lokal)

**Prasyarat:** PHP 8.3+, Composer, Node 22+, dan SQLite (paling mudah) atau MySQL/MariaDB.

### Jalur cepat
```bash
composer run setup
```
Perintah ini menjalankan: `composer install` → menyalin `.env.example` ke `.env` → `php artisan key:generate` → `php artisan migrate --force` → `npm install` → `npm run build`.

### Jalur manual
```bash
# 1. Dependensi
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database — opsi paling sederhana: SQLite
#    Set di .env:  DB_CONNECTION=sqlite  (kosongkan DB_HOST/DB_DATABASE/...)
touch database/database.sqlite

# 4. Migrasi + data dummy (akun & contoh listing/lelang/bahan jadi)
php artisan migrate:fresh --seed

# 5. Jalankan semua proses (server + queue + log + Vite) sekaligus
composer run dev
```
Buka URL yang ditampilkan (default `http://localhost:8000`).

> **Lelang realtime (opsional):** untuk update bid secara langsung, jalankan `php artisan reverb:start` dan isi variabel `REVERB_*` / `VITE_REVERB_*` di `.env`. Tanpa Reverb, aplikasi tetap berfungsi — data lelang ter-refresh saat halaman dimuat ulang.

---

## 7. Akun Demo

Disiapkan oleh `database/seeders/DatabaseSeeder.php`. **Password sama untuk semua: `daurin123`.**

| Peran | Email | Catatan |
| :-- | :-- | :-- |
| Rumah Tangga | `rt@daurin.test` | Ibu Wayan — sudah punya 1 listing Kardus (tersedia) |
| Pengepul | `pengepul@daurin.test` | Pak Made — menangani jenis **Kardus** |
| Industri | `industri@daurin.test` | PT Recycle Bali — sudah menaruh 1 tawaran lelang |

**Data dummy yang ikut dibuat** (cukup untuk mendemokan peta, dashboard, klasifikasi, dan lelang):
- 1 listing sampah RT (Kardus, 8 kg, dengan label & confidence AI)
- 1 jenis sampah ditangani pengepul (Kardus)
- 1 bahan baku pengepul (Kardus, 7,5 kg) berstatus **dilelang**
- 1 **lelang berlangsung** (durasi 6 jam, buyout Rp 60.000) + 1 bid awal Rp 36.000 dari industri
- 1 bahan baku jadi industri ("Lembaran Kardus Olahan")

---

## 8. Daftar Asumsi

Sesuai izin studycase (bagian J & K), asumsi yang diambil:

1. **Taksonomi sampah disederhanakan tapi konsisten.** 8 jenis di `app/Enums/JenisSampah.php`: Plastik PET, Plastik HDPE, Kertas, Kardus, Logam, Kaleng, Kaca, Elektronik. Konsisten dipakai di ketiga lapis (sampah RT → bahan baku → bahan baku jadi).
2. **Optimasi rute** memakai *nearest-neighbor* + jarak *haversine* (TSP penuh tidak diwajibkan), dengan estimasi jarak/waktu/biaya. Faktor & tarif (1.4×, Rp 3.000/km, 40 km/jam) adalah asumsi yang dapat disesuaikan.
3. **Negosiasi** minimal *offer → counter-offer → deal/batal* (`Pesanan` + `Negosiasi`), diperkaya mekanisme **lelang** (bid + buyout) untuk pembanding.
4. **Pembayaran disimulasikan** — endpoint `industri.pesanan.bayar` menandai `Pesanan.dibayar_at` tanpa payment gateway nyata.
5. **Klasifikasi AI** memakai layanan eksternal pra-terlatih (SigLIP2); bila tidak terjangkau, dipakai fallback berbasis nama file. Akurasi bukan kriteria utama.
6. **Lelang realtime** memakai Reverb; bila Reverb tidak dijalankan, aplikasi **degradasi anggun** (tetap jalan tanpa push langsung).
7. **Lokasi default** contoh data berada di sekitar area Bali (Denpasar) untuk demo peta yang masuk akal.

---

## 9. Bonus yang Diimplementasikan

- **Lelang & negosiasi realtime + riwayat** (Reverb/Echo, `Lelang`, `LelangBid`, `Negosiasi`, ruang `resources/js/pages/Lelang/Room.vue`).
- **Optimasi rute lengkap** — urutan kunjungan + estimasi jarak, waktu & biaya, dengan batas kapasitas.
- **Traceability material** — `BahanBaku.source_listing_id` & `BahanJadi.source_pesanan_id` menjaga jejak hulu-hilir.
- **Dashboard dampak** — `Dashboard.vue` menampilkan statistik per peran + metrik keberlanjutan (kg terdaur ulang, estimasi CO₂, nilai ekonomi, grafik volume per jenis via `BarChart.vue`).

---

## 10. Deployment

Aplikasi di-deploy dan dapat diakses publik di **https://wearegettingcooked.web.id**.

Infrastruktur: **VPS Rocky Linux** + **Nginx** + **MariaDB** + **PHP 8.4** + **Node 22**, dengan **auto-deploy via GitHub Actions** (SSH → `deploy.sh`: `git pull` → build → migrate → cache) dan SSL Let's Encrypt. Panduan provisioning lengkap ada di [`vps.md`](vps.md).

> Variabel sensitif (mis. `RESEND_API_KEY`, `REVERB_APP_SECRET`) **tidak** disimpan di repositori — lihat `.env.example` untuk daftar kunci yang perlu diisi.

---

## 11. Struktur Proyek (ringkas)

```
app/
├── Enums/               UserRole, JenisSampah, ListingStatus, BahanBakuStatus,
│                        LelangStatus, PesananStatus, BahanJadiStatus, ...
├── Http/
│   ├── Controllers/     Marketplace, Dashboard, Classify, ListingSampah, Penawaran,
│   │                    PengepulJenis, BahanBaku, Lelang, Pesanan, Negosiasi, BahanJadi
│   └── Middleware/       EnsureRole.php   (RBAC)
└── Models/              User, ListingSampah, PenawaranListing, PengepulJenis,
                         BahanBaku, Lelang, LelangBid, Pesanan, Negosiasi, BahanJadi

resources/js/
├── lib/route.ts         optimasi rute (nearest-neighbor + haversine)
└── pages/
    ├── Rt/              Index, Create (klasifikasi foto), Edit
    ├── Pengepul/        Ketersediaan (peta+rute), Jenis, BahanBaku, Nego
    ├── Industri/        Index, BahanJadi, Nego
    ├── Lelang/Room.vue  ruang lelang realtime
    └── Dashboard.vue    dashboard volume/transaksi/dampak

routes/web.php           definisi route per peran (rt./pengepul./industri.)
database/seeders/        DatabaseSeeder.php (akun demo + data dummy)
```
