# Praktikum 3 - Pemrograman Web: Mini Project CRUD F1 Merchandise Store

Proyek ini adalah implementasi aplikasi web manajemen produk merchandise Formula 1 (**F1 Merchandise Store**) berbasis **PHP murni** dan **MySQL/MariaDB**, dibuat untuk memenuhi kriteria penugasan **Mini Project (Pertemuan 3)**.

Aplikasi ini menerapkan standar keamanan web modern (pencegahan SQL Injection, XSS, CSRF), arsitektur alur **Post-Redirect-Get (PRG)**, antarmuka responsif berbasis **CSS Box Model & Flexbox**, serta fitur bonus pencarian GET, filter kategori, pagination, dan unggah gambar logo tim produk.

---

## 📌 Identitas Pengumpulan
- **Nama Mahasiswa** : M. Daffa Annafis
- **NIM**            : 250180071
- **Mata Kuliah**    : Pemrograman Web (Pertemuan 3)
- **Nama Berkas**    : `Praktikum3_250180071_M. Daffa Annafis.zip`

---

## 📂 Struktur Direktori Proyek

```text
Praktikum3_250180071_M. Daffa Annafis/
├── config/
│   └── db.php                  # Konfigurasi koneksi PDO ke MySQL & penanganan error
├── database/
│   └── store_db.sql            # File dump SQL (skema database & seed data awal)
├── includes/
│   ├── header.php              # Template header HTML, navbar Flexbox & flash message
│   ├── footer.php              # Template footer & script live preview / konfirmasi
│   └── helpers.php             # Utilitas keamanan (e() untuk escaping, CSRF, PRG flash, form old)
├── css/
│   └── style.css               # Styling Box Model, Flexbox product card, dan responsif
├── uploads/                    # Direktori penyimpanan berkas gambar produk
│   └── .gitkeep
├── index.php                   # Katalog produk (Read, Search GET, Filter Kategori, Pagination, Delete)
├── create.php                  # Form tambah produk & handler POST (Validasi Server-Side + PRG)
├── edit.php                    # Form update produk & handler POST (Validasi Server-Side + PRG)
├── delete.php                  # Handler hapus produk (Method POST + Proteksi CSRF Token + PRG)
├── test_runner.php             # Script pengujian otomatis untuk checklist demo slide 20
└── README.md                   # Dokumentasi lengkap, panduan menjalankan, & refleksi keamanan
```

---

## ⚙️ Persyaratan Sistem

- **PHP**: Versi 7.4 atau lebih baru (direkomendasikan PHP 8.0 - 8.2)
- **Ekstensi PHP**: `pdo_mysql`, `fileinfo`, `mbstring`, `session`
- **Database**: MySQL 5.7+ atau MariaDB 10.3+ (misal via XAMPP)
- **Web Browser**: Chrome, Firefox, Edge, atau Safari modern

---

## 🚀 Cara Menjalankan Aplikasi

### Opsi 1: Menggunakan PHP Built-in Server (Sangat Praktis)

1. **Jalankan Layanan MySQL**:
   - Buka XAMPP Control Panel dan klik tombol **Start** pada modul **MySQL**.

2. **Import Skema Database**:
   - Buka terminal/PowerShell di folder proyek:
     ```powershell
     # Import database store_db beserta data awal
     Get-Content database/store_db.sql | mysql -u root
     ```
   - *Atau*: Buka `http://localhost/phpmyadmin`, buat database `store_db`, lalu import file `database/store_db.sql`.

3. **Jalankan Server Lokal PHP**:
   - Di dalam folder `Praktikum3_NIM_Nama`, jalankan perintah:
     ```bash
     php -S localhost:8000
     ```

4. **Buka Aplikasi di Browser**:
   - Akses: [http://localhost:8000](http://localhost:8000)

---

### Opsi 2: Menggunakan Folder `htdocs` XAMPP

1. Ekstrak atau salin folder `Praktikum3_NIM_Nama` ke dalam direktori:
   `C:\xampp\htdocs\`
2. Pastikan service **Apache** dan **MySQL** pada XAMPP Control Panel telah diaktifkan (**Start**).
3. Import database `database/store_db.sql` via phpMyAdmin (`http://localhost/phpmyadmin`).
4. Buka browser dan akses:
   [http://localhost/Praktikum3_NIM_Nama](http://localhost/Praktikum3_NIM_Nama)

---

## 📋 Checklist Pengujian Demo (Slide 20)

Seluruh skenario pengujian pada checklist sebelum pengumpulan telah diuji dan berfungsi dengan baik:

| No | Skenario Uji | Tindakan / Input | Ekspektasi Perilaku | Hasil Pengujian |
|---|---|---|---|---|
| **1** | **Tambah produk valid** | Input: Nama valid (misal: "Webcam Full HD 1080p"), Kategori: Elektronik, Harga: 450000, Stok: 10. | Data berhasil disimpan dan langsung muncul di daftar katalog dengan badge stok hijau. | **Lolos (Valid)** |
| **2** | **Nama < 3 karakter** | Input: Nama "Ab" (2 karakter). | Form menolak pengiriman, data tidak tersimpan ke database, dan muncul notifikasi bahaya: *"Nama produk harus diisi dan minimal 3 karakter."* Nilai input sebelumnya dipertahankan. | **Lolos (Validasi Server)** |
| **3** | **Harga negatif / stok negatif** | Input: Harga `-10000` atau Stok `-5`. | Ditolak oleh server-side validation dengan pesan jelas: *"Harga harus berupa angka valid dan tidak boleh bernilai negatif (>= 0)"* atau *"Stok harus berupa bilangan bulat valid dan tidak boleh bernilai negatif (>= 0)"*. | **Lolos (Pesan Jelas)** |
| **4** | **Refresh setelah create** | Tambah produk valid, setelah tersimpan di katalog (`index.php`), tekan tombol **F5 / Refresh** berulang kali. | Tidak terjadi duplikasi data sama sekali karena menerapkan pola **Post-Redirect-Get (PRG)**. Request browser adalah `GET`, bukan `POST`. | **Lolos (Anti-Duplikasi)** |
| **5** | **Nama berisi `<b>Promo</b>`** | Input nama: `<b>Promo</b> Jaket Denim`. | Disanitasi melalui fungsi `e()` (`htmlspecialchars`). Karakter `<` dan `>` diubah menjadi entitas HTML `&lt;` dan `&gt;`. Teks tampil apa adanya sebagai `<b>Promo</b> Jaket Denim`, tidak dirender tebal atau mengeksekusi script XSS. | **Lolos (Anti-XSS)** |
| **6** | **Layar sempit / Mobile** | Buka browser dan resize jendela menjadi sempit (< 640px) atau gunakan Device Toolbar inspect element. | Elemen kartu produk membungkus rapi ke bawah (1 kolom di mobile, 2 di tablet, 3 di desktop) berkat layout CSS **Flexbox** (`flex-wrap: wrap`) dan Box Model (`box-sizing: border-box`). | **Lolos (Responsif)** |

---

## 🔒 Refleksi Keamanan (Slide 20)

> **Pertanyaan Refleksi**:
> *"Di bagian mana aplikasi paling rentan: input, query, output, atau alur request? Jelaskan kontrol keamanan yang telah Anda implementasikan."*

### 1. Analisis Kerentanan Tertinggi: **Output & Query**
Dalam aplikasi web CRUD konvensional, bagian yang **paling rentan secara statistik dan dampak risiko** adalah **Query (Basis Data)** dan **Output (Tampilan Pengguna)**:
- **Query** menjadi titik paling kritis karena jika data input disisipkan langsung ke dalam string query SQL (*string concatenation*), penyerang dapat melakukan **SQL Injection (SQLi)** yang berpotensi membocorkan seluruh isi database, menghapus tabel, atau melewati autentikasi.
- **Output** menjadi titik paling sering terabaikan karena jika data yang berasal dari pengguna (atau bahkan database) dicetak langsung tanpa filter, penyerang dapat menyuntikkan script JavaScript jahat (**Cross-Site Scripting / XSS**) yang dapat mencuri session cookie pengguna lain.
- **Alur Request** rentan terhadap **CSRF (Cross-Site Request Forgery)** terutama pada aksi-aksi destruktif seperti tombol hapus jika dibuat hanya menggunakan link GET biasa.

---

### 2. Kontrol Keamanan yang Telah Diimplementasikan

Aplikasi ini menerapkan pendekatan *Defense-in-Depth* pada setiap lapisan alur data:

```
[Pengguna] 
    │
    ├── (1) Validasi Input Server-Side & CSRF Token Guard
    ▼
[Controller / PHP Handler]
    │
    ├── (2) Query Execution via PDO Prepared Statements (Anti SQL Injection)
    ▼
[Database MySQL]
    │
    ├── (3) Post-Redirect-Get (PRG) Pattern (Anti Double Submit)
    ▼
[Output Renderer HTML]
    │
    └── (4) Context Escaping via htmlspecialchars() (Anti XSS)
```

#### A. Kontrol pada Lapisan Input (Input Validation & Whitelisting)
- Seluruh input form dibersihkan menggunakan `trim()` untuk membuang spasi kosong tidak sengaja.
- Validasi tipe dan batasan nilai dilakukan di **sisi server** (`create.php` & `edit.php`):
  - Minimal 3 karakter untuk nama produk (`mb_strlen`).
  - Nilai harga diverifikasi `is_numeric()` dan bernilai $\ge 0$.
  - Nilai stok diverifikasi `FILTER_VALIDATE_INT` dan bernilai $\ge 0$.
  - Validasi berkas gambar mencakup batas ukuran file (maksimal 2MB) dan verifikasi MIME Type sesungguhnya via `finfo_file` (bukan sekadar melihat ekstensi nama file).

#### B. Kontrol pada Lapisan Query (PDO Prepared Statements)
- Seluruh interaksi database (INSERT, SELECT, UPDATE, DELETE, serta Search) **100% menggunakan PDO Prepared Statements**.
- Nilai input dikirimkan secara terpisah melalui parameter binding (`:name`, `:price`, `:q_name`), bukan digabungkan ke dalam query SQL.
- Konfigurasi PDO:
  ```php
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false, // Mengaktifkan native prepared statements MySQL
  ```
- Dengan mekanisme ini, input berbahaya seperti `' OR '1'='1` akan diperlakukan sebagai literal teks murni dan tidak dapat mengubah struktur perintah SQL.

#### C. Kontrol pada Lapisan Output (Output Escaping)
- Seluruh variabel yang dicetak ke dokumen HTML diproses melalui fungsi helper `e($val)` yang membungkus:
  ```php
  htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  ```
- Opsi `ENT_QUOTES` memastikan baik tanda petik tunggal (`'`) maupun tanda petik ganda (`"`) ikut diubah menjadi entitas HTML.
- Pengujian: teks input `<b>Promo</b>` akan dirender aman sebagai `&lt;b&gt;Promo&lt;/b&gt;`.

#### D. Kontrol pada Lapisan Alur Request (CSRF & PRG Pattern)
- **Anti CSRF**:
  - Tombol hapus data **tidak menggunakan link GET** (`<a href="delete.php?id=1">`), melainkan menggunakan formulir `POST`.
  - Formulir `POST` dilengkapi dengan token CSRF acak yang tersimpan di sesi (`$_SESSION['csrf_token']`).
  - `delete.php` memvalidasi kesesuaian token menggunakan `hash_equals()` yang aman dari serangan *timing attack*.
- **Post-Redirect-Get (PRG)**:
  - Setiap kali formulir dikirimkan (tambah, edit, hapus), server memproses data lalu segera mengeluarkan header redirect (`header("Location: index.php"); exit;`).
  - Pesan status disampaikan melalui **Session Flash Message** yang otomatis terhapus setelah sekali dibaca.
  - Alur ini mencegah pengguna menduplikasi data ketika menekan tombol reload/refresh pada browser.

---

## 🌟 Fitur Bonus yang Diimplementasikan
1. **Pencarian GET Dinamis (Slide 18)**: Pencarian nama & deskripsi menggunakan parameter query SQL aman (`:q_name`, `:q_desc`).
2. **Filter Kategori**: Filter instan berdasarkan kategori barang.
3. **Pagination**: Pembagian halaman katalog (6 produk per halaman) yang mempertahankan parameter query pencarian saat navigasi.
4. **Unggah Foto Produk**: Fitur upload berkas gambar dengan validasi ketat (JPG, PNG, WEBP $\le$ 2MB), penamaan hash unik yang aman, serta fallback ikon placeholder kategori jika tanpa foto.
5. **Script Pengujian Otomatis (`test_runner.php`)**: Memverifikasi ke-6 checklist demo dan kriteria keamanan dalam sekali eksekusi terminal.
