# Panduan Deploy — Hostinger / cPanel / VPS

Panduan menaikkan **CO Inventory** ke server, termasuk penanganan error
`403 Forbidden` yang biasa muncul saat pertama kali unggah.

---

## 1. Penyebab 403 dan Cara Mengatasinya

Laravel menaruh berkas yang boleh diakses publik di folder **`public/`**, sementara
hosting mengarahkan domain ke **`public_html/`**. Bila seluruh proyek diunggah ke
`public_html/`, Apache mencari `public_html/index.php` yang tidak ada → **403**.

Ada dua cara. Pilih salah satu.

### Opsi A — Ubah document root (PALING AMAN, disarankan)

Di hPanel Hostinger: **Websites → Kelola → Advanced → Change Website Root**,
arahkan ke folder `public`.

Struktur di server:

```
/home/uXXXX/domains/namadomain.com/
├── app/  bootstrap/  config/  database/  routes/  storage/  vendor/   ← di luar web root
└── public/            ← document root diarahkan ke sini
    ├── index.php
    ├── .htaccess
    └── build/
```

Keunggulannya: `.env`, `storage/`, dan `vendor/` **tidak mungkin** diakses lewat
browser karena berada di luar web root. Dengan cara ini `.htaccess` di root proyek
tidak diperlukan.

### Opsi B — Pakai `.htaccess` di root (bila document root tidak bisa diubah)

Unggah seluruh proyek ke dalam `public_html/`. Berkas **[`.htaccess`](../.htaccess)**
di root proyek sudah disiapkan dan akan:

1. mengalihkan semua permintaan ke `public/`
2. memblokir `.env`, `composer.json`, `artisan`, dan folder internal Laravel
3. mematikan daftar isi folder (`Options -Indexes`)

Struktur di server:

```
public_html/
├── .htaccess          ← pengalih ke public/ (sudah ada di repo)
├── app/  config/  routes/  storage/  vendor/  ...
└── public/
    ├── index.php
    └── .htaccess
```

> ⚠️ Dengan Opsi B seluruh proyek berada di dalam web root. `.htaccess` sudah
> memblokir berkas sensitif, tetapi bila `mod_rewrite` mati, proteksi ikut mati.
> Karena itu Opsi A tetap lebih disarankan bila memungkinkan.

**Opsi B hanya untuk domain/subdomain root**, mis. `namadomain.com` atau
`inventory.namadomain.com`. Bila aplikasi dipasang di **subfolder** seperti
`namadomain.com/inventory/`, pengalihan ini membuat Laravel salah mengenali
alamat dasar sehingga semua rute jadi 404. Untuk kasus subfolder, gunakan
**Opsi A** dengan membuat subdomain yang document root-nya diarahkan ke
folder `public`.

---

## 2. Berkas yang Wajib Ada di Server

| Folder | Cara menyediakannya |
|---|---|
| `public/build/` | **Sudah ikut ter-commit** (sengaja dikeluarkan dari `.gitignore`, lihat catatan di bawah). Cukup pastikan `npm run build` dijalankan sebelum commit. |
| `vendor/` | Jalankan `composer install --no-dev --optimize-autoloader` di server. Bila tidak ada akses SSH, unggah folder `vendor/` dari lokal. |

Yang **tidak perlu** diunggah: `node_modules/`, `tests/`, `.git/`.

> ### ⚠️ Alur wajib setiap kali mengubah tampilan
>
> Hostinger shared hosting **tidak menyediakan Node.js**, sehingga aset tidak bisa
> dibangun di server. Karena itu `public/build/` sengaja ikut disimpan di Git.
>
> ```bash
> npm run build          # WAJIB dijalankan lebih dulu
> git add public/build
> git commit -m "..."
> git push
> ```
>
> Kalau langkah `npm run build` terlewat, server akan memakai aset lama — atau
> gagal sama sekali dengan error *Vite manifest not found*.

> ### ❗ Jangan pernah mengunggah berkas `public/hot`
>
> Berkas itu hanya muncul saat `npm run dev` berjalan dan menandakan Laravel harus
> mengambil aset dari server Vite lokal. Bila ikut terunggah, seluruh CSS/JS di
> produksi akan gagal dimuat. Berkas ini sudah diabaikan Git — pastikan tetap
> demikian bila Anda mengunggah manual lewat FTP.

---

## 3. Konfigurasi `.env` untuk Produksi

Salin `.env.example` menjadi `.env` di server, lalu sesuaikan:

```dotenv
APP_NAME="CO Inventory"
APP_ENV=production
APP_KEY=                          # isi dengan: php artisan key:generate
APP_DEBUG=false                   # WAJIB false di produksi
APP_URL=https://namadomain.com    # pakai https bila SSL aktif
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=uXXXX_co_inventory    # nama DB dari hPanel
DB_USERNAME=uXXXX_admin
DB_PASSWORD=**********

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database         # ganti ke redis bila tersedia (Fase 4)
```

Tiga hal yang sering terlewat:

- **`APP_DEBUG=false`** — bila `true`, pesan error akan membocorkan isi `.env`
  (termasuk password database) ke pengunjung.
- **`APP_URL` harus persis sama dengan alamat yang Anda ketik di browser** untuk
  membuka halaman login — termasuk `https://` dan subfolder bila ada. URL aset
  CSS/JS dibangun dari nilai ini, jadi kalau meleset, tampilan muncul tanpa gaya
  meski `public/build/` sudah benar. Contoh:

  ```dotenv
  APP_URL=https://stock.cok-analytics.com          # bila memakai subdomain
  APP_URL=https://cok-analytics.com/stock          # bila berada di subfolder
  ```

  Setelah mengubahnya, wajib jalankan `php artisan config:cache`.
- **`APP_KEY` tidak boleh kosong** — kalau kosong, semua data terenkripsi
  (termasuk `warehouses.api_token`) gagal dibaca.

---

## 4. Urutan Perintah Setelah Unggah

Lewat SSH (atau Terminal di hPanel):

```bash
cd ~/domains/namadomain.com          # sesuaikan

composer install --no-dev --optimize-autoloader

php artisan key:generate             # hanya bila APP_KEY masih kosong
php artisan migrate --force
php artisan db:seed --force          # hanya saat instalasi pertama
php artisan storage:link

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Setiap kali `.env` diubah, jalankan ulang `php artisan config:cache`
> (atau `config:clear`). Tanpa itu perubahan tidak terbaca.

**Tanpa akses SSH?** Semua perintah di atas bisa dijalankan dari halaman
*Terminal* hPanel. Bila benar-benar tidak ada, jalankan `migrate`/`seed` di lokal
lalu impor database lewat phpMyAdmin, dan unggah `vendor/` manual.

---

## 5. Izin Folder

```bash
chmod -R 775 storage bootstrap/cache
```

Bila muncul error *"failed to open stream: Permission denied"*, ini penyebabnya.
Di sebagian hosting perlu `755` alih-alih `775`.

---

## 6. Cron untuk Sinkronisasi (persiapan Fase 4)

Begitu penarikan API aktif, tambahkan satu cron job di hPanel
(**Advanced → Cron Jobs**), dijalankan **setiap menit**:

```
* * * * * cd /home/uXXXX/domains/namadomain.com && php artisan schedule:run >> /dev/null 2>&1
```

Satu baris ini cukup untuk seluruh penjadwalan Laravel.

---

## 7. Daftar Masalah Umum

| Gejala | Penyebab | Solusi |
|---|---|---|
| **403 Forbidden** | Document root menunjuk ke folder proyek, bukan `public/` | Opsi A atau B di bagian 1 |
| **403 walau `.htaccess` ada** | `mod_rewrite` mati / `AllowOverride None` | Hubungi hosting, atau pakai Opsi A |
| **500 Internal Server Error** | `APP_KEY` kosong, izin `storage/` salah, atau `vendor/` belum ada | Cek `storage/logs/laravel.log` |
| **Halaman putih polos** | `APP_DEBUG=false` menyembunyikan error | Baca `storage/logs/laravel.log` |
| **`Vite manifest not found at .../public/build/manifest.json`** | Folder `public/build/` belum ada di server | Jalankan `npm run build` di lokal, lalu unggah folder `public/build/` (3 berkas, ±145 KB) |
| **Tampilan tanpa CSS/JS, tapi manifest ada** | `APP_URL` salah sehingga URL aset meleset | Samakan `APP_URL` dengan alamat asli aplikasi, lalu `php artisan config:cache` |
| **Aset menunjuk ke `localhost:5173`** | Berkas `public/hot` ikut terunggah | Hapus `public/hot` di server |
| **419 Page Expired** saat login | Sesi/cookie bermasalah | Pastikan `APP_URL` benar, jalankan `php artisan config:cache` |
| **Rute selain beranda 404** | `mod_rewrite` mati atau `.htaccess` tidak terbaca | Aktifkan `RewriteBase /` di `public/.htaccess` |
| **Aset campur http/https** | `APP_URL` masih `http://` | Ubah ke `https://`, lalu `config:cache` |
| **Waktu meleset 7 jam** | `APP_TIMEZONE` belum diset | Set `Asia/Jakarta`, lalu `config:cache` |

---

## 8. Pemeriksaan Setelah Deploy

- [ ] Buka domain → halaman login tampil dengan rapi (CSS aktif)
- [ ] Login `admin@co.test` berhasil → masuk ke Dashboard
- [ ] Halaman Inventory, Gudang, Divisi, User, Role, Permission semua terbuka
- [ ] Pencarian, pengurutan kolom, dan pagination berfungsi
- [ ] Tanggal tampil dalam WIB, bukan meleset 7 jam
- [ ] `https://namadomain.com/.env` → **403 atau 404**, bukan menampilkan isi berkas
- [ ] `https://namadomain.com/storage/logs/laravel.log` → **tidak** bisa dibuka
- [ ] **Ganti password akun `admin@co.test`** — masih memakai password bawaan
