# Kontrak API — Data Stok Gudang

**Untuk:** Tim pengembang sistem gudang
**Dari:** Tim CO Inventory (sistem pusat)
**Versi:** 1.0

Dokumen ini mendefinisikan format API yang **wajib diikuti** agar data stok dari setiap
gudang dapat ditarik secara otomatis dan seragam oleh sistem pusat. Mohon diikuti
persis — perbedaan format sekecil apa pun harus dikonfirmasi lebih dulu.

---

## 1. Prinsip Umum

| Aspek | Ketentuan |
|---|---|
| Protokol | HTTPS wajib (sertifikat valid) |
| Format | JSON, `Content-Type: application/json; charset=utf-8` |
| Encoding | UTF-8 |
| Penamaan field | `snake_case`, persis seperti dokumen ini |
| Nilai kosong | Gunakan `null` — **jangan** `""`, `"-"`, `"null"`, atau `0` |
| Angka | Kirim sebagai JSON number, **bukan string**. Contoh: `12` bukan `"12"` |
| Tanggal/waktu | ISO-8601 **dengan offset zona waktu**. Contoh: `2026-07-23T14:30:00+07:00` |
| Versi | Sertakan versi di URL: `/api/v1/...` |

> ⚠️ **Timestamp tanpa zona waktu tidak diterima.** `2026-07-23 14:30:00` ambigu dan
> akan menyebabkan data hilang saat sinkronisasi. Gunakan `+07:00` atau UTC (`Z`).

---

## 2. Endpoint Utama — Daftar Stok

```
GET /api/v1/stocks
```

Mengembalikan daftar stok barang di gudang ini.

### 2.1 Parameter Query

| Parameter | Tipe | Wajib | Default | Keterangan |
|---|---|---|---|---|
| `updated_since` | ISO-8601 datetime | tidak | — | Hanya kirim barang yang berubah **pada atau setelah** waktu ini (`>=`). Termasuk **tanggal dan jam**. |
| `updated_until` | ISO-8601 datetime | tidak | — | Batas atas perubahan (`<=`). Dipakai untuk penarikan rentang / perbaikan data. |
| `as_of` | tanggal `Y-m-d` | tidak | — | **Posisi stok pada penutupan tanggal ini** (snapshot historis). `qty` = stok per tanggal tsb, bukan stok terkini. Kosong = stok terkini. Tidak digabung dengan `updated_since/until`. |
| `page` | integer | tidak | `1` | Halaman ke-. |
| `per_page` | integer | tidak | `100` | Jumlah baris per halaman. Maksimum `500`. |

> ℹ️ **Bedakan dua filter tanggal:** `updated_since/until` menyaring berdasarkan **kapan
> baris terakhir berubah** (dipakai sinkronisasi bertahap). `as_of` mengembalikan
> **posisi stok pada suatu tanggal** — seluruh SKU tetap dikirim, hanya nilai `qty`-nya
> yang mencerminkan keadaan tanggal itu. Halaman Inventory pusat memakai `as_of`.

**Filter tanggal + jam** adalah inti dari sinkronisasi bertahap (incremental). Sistem
pusat akan memanggil endpoint ini tiap beberapa menit dengan `updated_since` berisi
waktu sinkronisasi terakhir, sehingga hanya data yang berubah yang dikirim.

### 2.2 Contoh Request

Ambil semua perubahan sejak 23 Juli 2026 pukul 08:30 WIB:

```
GET /api/v1/stocks?updated_since=2026-07-23T08%3A30%3A00%2B07%3A00&page=1&per_page=100
Authorization: Bearer <API_TOKEN>
Accept: application/json
```

> Catatan: karakter `:` dan `+` harus di-URL-encode menjadi `%3A` dan `%2B`.

Ambil rentang waktu tertentu (mis. rekonsiliasi harian):

```
GET /api/v1/stocks?updated_since=2026-07-22T00%3A00%3A00%2B07%3A00&updated_until=2026-07-23T00%3A00%3A00%2B07%3A00
```

Tanpa parameter waktu = ambil **seluruh** data (full sync):

```
GET /api/v1/stocks?page=1&per_page=500
```

Posisi stok pada tanggal tertentu (mis. stok akhir 30 Juni 2026):

```
GET /api/v1/stocks?as_of=2026-06-30&page=1&per_page=100
```

### 2.3 Format Response (HTTP 200)

> 📁 **Contoh lengkap tersedia di folder [`contoh-response-api/`](contoh-response-api/)** —
> mencakup pagination, response kosong, barang terhapus, gudang divisi lain, dan
> berbagai kondisi error. Mohon dipakai sebagai acuan utama dan dibandingkan
> langsung dengan keluaran endpoint Anda.

Struktur amplop ini **tetap dan tidak boleh berubah**:

```json
{
  "success": true,
  "meta": {
    "warehouse_code": "JKT-01",
    "server_time": "2026-07-23T14:32:10+07:00",
    "page": 1,
    "per_page": 100,
    "total": 1284,
    "total_pages": 13
  },
  "data": [
    {
      "sku": "SKU-4300",
      "name": "Masker N95",
      "category": "APD",
      "uom": "pcs",
      "qty": 520,
      "min_qty": 100,
      "status": "active",
      "updated_at": "2026-07-23T11:05:00+07:00"
    },
    {
      "sku": "SKU-6001",
      "name": "Air Mineral 600ml",
      "category": "Konsumsi",
      "uom": "box",
      "qty": 0,
      "min_qty": 120,
      "status": "active",
      "updated_at": "2026-07-23T09:47:31+07:00"
    }
  ]
}
```

### 2.4 Field `meta`

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `warehouse_code` | string | ✅ | Kode gudang ini. Dipakai untuk verifikasi bahwa data tidak tertukar. |
| `server_time` | datetime | ✅ | Waktu server saat response dibuat. Dipakai pusat sebagai penanda sinkronisasi berikutnya. |
| `page` | integer | ✅ | Halaman saat ini. |
| `per_page` | integer | ✅ | Jumlah baris per halaman. |
| `total` | integer | ✅ | Total baris yang cocok dengan filter (bukan hanya halaman ini). |
| `total_pages` | integer | ✅ | Total halaman. |

### 2.5 Field pada `data[]`

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `sku` | string | ✅ | Kode barang. **Unik dan stabil selamanya** di gudang ini. Maks 64 karakter. |
| `name` | string | ✅ | Nama barang. Maks 255 karakter. |
| `category` | string \| null | ✅ | Kategori barang. Boleh `null`. |
| `uom` | string | ✅ | Satuan: `pcs`, `box`, `kg`, `liter`, `roll`, dst. Konsisten untuk SKU yang sama. |
| `qty` | number | ✅ | Stok saat ini. Boleh desimal (maks 3 angka di belakang koma). Tidak boleh negatif. |
| `min_qty` | number \| null | ✅ | Batas minimum stok versi gudang. Boleh `null` bila tidak dipakai. |
| `status` | string | ✅ | `active` \| `inactive` \| `deleted`. Lihat bagian 5.3. |
| `updated_at` | datetime | ✅ | Kapan baris ini **terakhir berubah** di sistem gudang. Lihat bagian 5.2. |

> 💰 **Data harga tidak diminta.** Sistem pusat hanya memantau kuantitas stok, jadi
> mohon **jangan** menyertakan field harga, nilai persediaan, atau HPP dalam response.
> Selain tidak terpakai, ini menghindari data komersial keluar dari sistem Anda
> tanpa perlu.

---

## 3. Endpoint Kesehatan (Health Check)

```
GET /api/v1/health
```

Dipakai halaman monitoring pusat untuk mengecek gudang masih online. Harus ringan
dan tidak menyentuh database berat.

```json
{
  "success": true,
  "warehouse_code": "JKT-01",
  "server_time": "2026-07-23T14:32:10+07:00"
}
```

---

## 4. Autentikasi

Gunakan **Bearer token** pada header:

```
Authorization: Bearer <API_TOKEN>
```

Ketentuan:

- Token dibuat oleh pihak gudang dan diberikan ke tim pusat melalui kanal aman
  (bukan email/chat biasa).
- Token bersifat **read-only** — hanya untuk membaca data stok.
- Beri tahu tim pusat bila token akan dirotasi, minimal **7 hari** sebelumnya.
- Mohon daftarkan IP server pusat ke allowlist (IP akan kami kirim terpisah).

---

## 5. Aturan Wajib

Bagian ini menentukan apakah sinkronisasi berjalan benar atau merusak data.
Mohon dibaca dengan teliti.

### 5.1 Urutan data harus deterministik

Data **wajib** diurutkan dengan:

```sql
ORDER BY updated_at ASC, sku ASC
```

**Alasan:** tanpa urutan yang pasti (khususnya tanpa `sku` sebagai pemecah seri),
barang bisa terlewat atau terduplikasi saat pusat membaca halaman 2, 3, dst.
Ini penyebab kehilangan data yang paling sering terjadi dan paling sulit dilacak.

### 5.2 `updated_at` harus selalu berubah

Setiap kali **apa pun** pada baris berubah — qty, nama, kategori, status — kolom
`updated_at` **wajib** ikut diperbarui.

**Alasan:** pusat hanya menarik data dengan `updated_at >= updated_since`. Bila stok
berubah tapi `updated_at` tidak, perubahan itu **tidak akan pernah terkirim** dan
stok di pusat akan salah selamanya.

### 5.3 Barang yang dihapus harus tetap dikirim

Jangan menghilangkan barang dari response. Bila barang dihapus atau dihentikan,
kirim tetap dengan `status` diubah:

- `active` — barang normal
- `inactive` — tidak dipakai lagi, tapi stok masih ada
- `deleted` — sudah dihapus di sistem gudang

**Alasan:** pada sinkronisasi bertahap, barang yang hilang begitu saja dari response
tidak bisa dibedakan dari "tidak ada perubahan". Akibatnya stok hantu tertinggal di
pusat. Perbarui juga `updated_at` saat status berubah.

### 5.4 SKU tidak boleh didaur ulang

Satu kode SKU mewakili satu barang **selamanya**. Jangan pernah memakai ulang kode
milik barang lama untuk barang baru — data historis akan tercampur.

### 5.5 Batas dan performa

- Sebutkan **rate limit** (request per menit) yang diizinkan.
- Endpoint harus mampu melayani `per_page=500` dalam waktu wajar (< 10 detik).
- Beri tahu bila ada jam sibuk yang sebaiknya dihindari untuk penarikan penuh.

---

## 6. Format Error

Gunakan HTTP status code yang sesuai, dengan body seragam:

```json
{
  "success": false,
  "error": {
    "code": "INVALID_TOKEN",
    "message": "Token tidak valid atau sudah kedaluwarsa."
  }
}
```

| HTTP | Kapan dipakai | `error.code` contoh |
|---|---|---|
| `400` | Parameter salah (mis. format tanggal keliru) | `INVALID_PARAMETER` |
| `401` | Token tidak ada / salah | `INVALID_TOKEN` |
| `403` | Token benar tapi tidak berhak | `FORBIDDEN` |
| `429` | Melebihi rate limit | `RATE_LIMIT_EXCEEDED` |
| `500` | Kesalahan internal | `INTERNAL_ERROR` |

Untuk `429`, mohon sertakan header `Retry-After` (dalam detik).

> Penting: jangan mengembalikan HTTP `200` untuk kondisi error. Sistem pusat
> membedakan gangguan sementara (akan dicoba ulang) dari kesalahan permanen
> berdasarkan status code.

---

## 7. Yang Perlu Diisi Tiap Gudang

Mohon lengkapi dan kirim balik ke tim pusat:

| Item | Jawaban |
|---|---|
| Kode gudang (`warehouse_code`) | |
| Base URL produksi | |
| Base URL sandbox/uji | |
| API token (kanal aman) | |
| Zona waktu yang dipakai | |
| Rate limit (request/menit) | |
| `per_page` maksimum yang didukung | |
| Mendukung `updated_since` / `updated_until`? | |
| Mendukung `status = deleted`? | |
| Jam sibuk yang dihindari | |
| Kontak teknis (nama, email, telepon) | |

---

## 8. Checklist Sebelum Dinyatakan Siap

- [ ] Response persis mengikuti struktur bagian 2.3
- [ ] Semua timestamp ISO-8601 **dengan offset zona waktu**
- [ ] `updated_since` dan `updated_until` berfungsi sampai level **jam, menit, detik**
- [ ] Urutan `updated_at ASC, sku ASC` sudah diterapkan
- [ ] `updated_at` berubah setiap kali baris berubah
- [ ] Barang terhapus dikirim dengan `status = deleted`
- [ ] Pagination benar saat `total` melebihi `per_page`
- [ ] Error memakai HTTP status code yang sesuai
- [ ] Endpoint `/api/v1/health` tersedia
- [ ] Sandbox dapat diakses tim pusat untuk pengujian
