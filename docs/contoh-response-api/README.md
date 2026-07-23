# Contoh Response API — Acuan Wajib

Berkas-berkas JSON di folder ini adalah **contoh keluaran yang kami harapkan**.
Mohon endpoint Anda menghasilkan struktur yang **persis sama** (nama field, tipe
data, format tanggal, susunan amplop). Aturan lengkapnya ada di
[`../api-kontrak-stok-gudang.md`](../api-kontrak-stok-gudang.md).

Cara paling cepat memastikan sudah benar: panggil endpoint Anda, lalu bandingkan
hasilnya baris per baris dengan berkas di sini.

---

## Daftar Berkas

| Berkas | Skenario | HTTP |
|---|---|---|
| `01-stocks-halaman-1.json` | Daftar stok, halaman pertama dari 2 | `200` |
| `02-stocks-halaman-2.json` | Halaman terakhir | `200` |
| `03-stocks-kosong.json` | Tidak ada perubahan sejak `updated_since` | `200` |
| `04-stocks-status-nonaktif-terhapus.json` | Barang nonaktif & terhapus | `200` |
| `05-stocks-divisi-teknik.json` | Gudang divisi lain (katalog & kode SKU berbeda) | `200` |
| `06-health.json` | Health check | `200` |
| `07-error-400-parameter-salah.json` | Format parameter keliru | `400` |
| `08-error-401-token-invalid.json` | Token salah/kedaluwarsa | `401` |
| `09-error-429-rate-limit.json` | Melebihi rate limit | `429` |

---

## Hal Penting pada Tiap Contoh

### 01 & 02 — Pagination

- `meta.total` = **12** adalah jumlah seluruh baris yang cocok filter, **bukan**
  jumlah baris di halaman ini. Halaman 1 berisi 10 baris, halaman 2 berisi 2 baris.
- Perhatikan dua baris pertama pada halaman 1: `SKU-1001` dan `SKU-2044` punya
  `updated_at` yang **sama persis**. Urutannya ditentukan oleh `sku` sebagai
  pemecah seri. Inilah alasan urutan wajib `ORDER BY updated_at ASC, sku ASC` —
  tanpa itu, baris bisa berpindah halaman di antara dua permintaan dan datanya
  terlewat.
- `SKU-7200` pada halaman 2 memperlihatkan penggunaan `null` untuk `category` dan
  `min_qty`. Gunakan `null`, bukan `""`, `"-"`, atau `0`.

### 03 — Response kosong

Ini kondisi **paling sering terjadi** saat sinkronisasi berjalan tiap beberapa
menit dan tidak ada perubahan. Yang wajib diperhatikan:

- Tetap `HTTP 200` dan `success: true` — **bukan** `404` atau error.
- `data` adalah array kosong `[]`, **bukan** `null`.
- `meta` tetap lengkap dengan `total: 0` dan `total_pages: 0`.

### 04 — Barang nonaktif dan terhapus

Barang yang dihapus di sistem Anda **tetap harus muncul** di response dengan
`status: "deleted"`, disertai `updated_at` yang diperbarui saat penghapusan.

Kalau barang langsung hilang dari response, sistem kami tidak bisa membedakannya
dari "tidak ada perubahan", sehingga stoknya akan tertinggal selamanya di pusat.

Nilai `status` yang diterima hanya: `active`, `inactive`, `deleted`.

### 05 — Gudang divisi lain

Contoh ini memakai `warehouse_code: "MDN-04"` dengan kode SKU berpola berbeda
(`TK-xxxx`). Setiap gudang cukup mengirim katalognya sendiri — tidak perlu
menyamakan kode dengan gudang lain.

Perhatikan `TK-3110` memakai `qty: 247.5` (desimal). Nilai desimal diperbolehkan
maksimal 3 angka di belakang koma, dan dikirim sebagai **angka JSON**, bukan
string (`247.5`, bukan `"247.5"`).

### 06 — Health check

Dipakai halaman pemantauan kami untuk menandai gudang online/offline. Harus
ringan dan tidak melakukan query berat.

### 07–09 — Error

- Gunakan **HTTP status code yang sesuai**. Jangan mengembalikan `200` untuk
  kondisi error — sistem kami membedakan gangguan sementara (akan dicoba ulang
  otomatis) dari kesalahan permanen berdasarkan status code.
- Untuk `429`, sertakan juga header `Retry-After` berisi jumlah detik.

---

## Checklist Verifikasi Mandiri

Sebelum menyerahkan endpoint, mohon pastikan:

- [ ] Nama field persis sama, `snake_case`, tanpa field tambahan yang tidak disepakati
- [ ] Semua `updated_at` dan `server_time` memakai ISO-8601 **beserta offset zona waktu**
- [ ] `qty` dan `min_qty` bertipe angka JSON — bukan string
- [ ] Nilai kosong memakai `null`
- [ ] Urutan `updated_at ASC, sku ASC` sudah diterapkan (uji dengan data ber-timestamp kembar)
- [ ] `updated_since` bekerja sampai satuan **detik**, bukan hanya tanggal
- [ ] `meta.total` mencerminkan seluruh hasil filter, bukan jumlah baris satu halaman
- [ ] Response kosong tetap `200` dengan `data: []`
- [ ] Barang terhapus dikirim dengan `status: "deleted"`
- [ ] Error memakai HTTP status code yang sesuai
