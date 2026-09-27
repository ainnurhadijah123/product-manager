# Product Manager — Mini Project (Pemrograman Web, Pertemuan 3)

Aplikasi CRUD sederhana untuk mengelola data produk menggunakan **PHP native + PDO + MySQL**,
dengan validasi server-side, pola **Post–Redirect–Get (PRG)**, proteksi **CSRF**, pencegahan **XSS**,
dan tampilan responsif menggunakan **CSS Box Model + Flexbox**.

## Struktur Proyek

```
product-manager/
├── config/
│   ├── db.php          # Koneksi PDO ke database
│   └── helpers.php     # Fungsi bantu: CSRF token, escape HTML, format rupiah
├── public/
│   ├── index.php        # READ semua produk + search/filter (bonus)
│   ├── create.php       # Form tambah produk + validasi + INSERT + PRG
│   ├── edit.php          # READ satu produk by ID + form UPDATE
│   ├── delete.php        # DELETE produk (POST + CSRF wajib) + PRG
│   └── assets/
│       └── style.css     # Box Model + Flexbox (grid produk responsif)
├── database/
│   └── store_db.sql      # Skema tabel products + data contoh
└── README.md
```

## Cara Menjalankan (XAMPP)

1. **Salin folder proyek** ke dalam folder web server, misalnya:
    XAMPP: `C:\xampp\htdocs\product-manager`

2. **Buat database** dengan mengimpor `database/store_db.sql`:
   - Buka **phpMyAdmin** → tab **Import** → pilih file `database/store_db.sql` → klik **Go**.
   - Atau lewat CLI:
     ```bash
     mysql -u root -p < database/store_db.sql
     ```

3. **Sesuaikan koneksi database** (jika perlu) di `config/db.php`:
   ```php
   $host   = 'localhost';
   $dbname = 'store_db';
   $user   = 'root';
   $pass   = '';
   ```

4. **Jalankan Apache & MySQL**, lalu buka di browser:
   ```
   http://localhost/product-manager/public/index.php
   ```

## Fitur

| Fitur | Keterangan |
|---|---|
| **Create** | Form tambah produk (nama, kategori, harga, stok) dengan validasi server-side dan pola PRG agar refresh tidak menduplikasi data. |
| **Read** | Daftar produk ditampilkan sebagai card responsif (Flexbox), diurutkan dari produk terbaru. |
| **Update** | Form edit terisi otomatis dari data lama, lalu memproses `UPDATE` berdasarkan ID. |
| **Delete** | Hanya menerima `POST` dan wajib menyertakan token **CSRF** yang valid. |
| **Validasi** | Nama ≥ 3 karakter & unik, harga > 0, stok ≥ 0 — divalidasi di server, bukan hanya di HTML. |
| **Keamanan** | Semua query memakai **prepared statement (PDO)**; semua output teks memakai `htmlspecialchars()` untuk mencegah XSS. |
| **Bonus: Search/Filter** | Pencarian produk berdasarkan nama/kategori memakai `GET`, tetap melalui prepared statement (`LIKE :q`). |

## Skenario Pengujian (Checklist Demo)

Tambah produk dengan data valid → muncul di daftar.
Nama produk < 3 karakter → ditolak dengan pesan error.
Harga negatif / stok negatif → ditolak dengan pesan error.
Refresh halaman setelah create berhasil → tidak terjadi duplikasi data (PRG).
Nama produk berisi `<b>Promo</b>` → tampil sebagai teks, bukan tag HTML aktif.
Tampilan di layar sempit → card membungkus rapi (flex-wrap).

## Catatan

- Kode ini ditulis dengan PHP native (tanpa framework) agar konsep dasar
  HTTP, PDO, validasi, dan keamanan terlihat jelas langkah demi langkah.
- Untuk kebutuhan produksi sesungguhnya, sebaiknya tambahkan hashing
  password bila ada fitur login, serta konfigurasi `error_reporting`
  yang tidak menampilkan detail error ke pengguna akhir.
