# Portofiles

Platform discovery karya seni, dibangun dari desain Figma memakai **HTML, CSS, JavaScript (vanilla), PHP, dan MySQL**. Tidak perlu framework, Composer, atau npm.

## Cara menjalankan di XAMPP

1. Salin folder `portofiles` ke `C:\xampp\htdocs\` (Windows) atau `/Applications/XAMPP/htdocs/` (macOS).
2. Buka **XAMPP Control Panel**, lalu **Start** Apache dan MySQL.
3. Buka `http://localhost/phpmyadmin`, pilih tab **Import**, pilih file `sql/portofiles.sql`, lalu klik **Go**.
   File ini membuat database `portofiles` sekaligus data contohnya.
4. Buka `http://localhost/portofiles/`.

Kalau user atau password MySQL kamu bukan `root` tanpa password, ubah di `config/config.php`.

### Akun demo

| Email | Password |
| --- | --- |
| maya@portofiles.test | portofiles123 |

Akun lain (`luca@`, `sofia@`, `noah@`, `amara@`, `yuki@` + `portofiles.test`) memakai password yang sama.

## Struktur folder

```
portofiles/
├── index.php          Discover: hero, filter kategori, galeri, lightbox
├── login.php          Sign in
├── register.php       Join (daftar akun)
├── logout.php
├── dashboard.php      Your studio: overview, my projects, saved work, settings
├── upload.php         Upload / edit project (drag & drop gambar)
├── actions.php        Publish, pindah ke draft, hapus project
├── profile.php        Profil publik artis (?u=username)
├── artists.php        Daftar artis
├── about.php          About, community guidelines, privacy
├── api/               Endpoint AJAX (JSON)
│   ├── artworks.php   Filter, sort, search, "Explore more"
│   ├── like.php       Like / appreciation
│   ├── follow.php     Follow artis
│   └── view.php       Hitung views saat karya dibuka
├── config/config.php  Pengaturan database & aplikasi
├── includes/          Koneksi DB, helper, auth, layout, partial
├── assets/css/style.css
├── assets/js/main.js  Semua animasi & interaksi
├── assets/img/        Gambar karya dari Figma
├── uploads/           Gambar hasil upload user
└── sql/portofiles.sql Skema + data contoh
```

## Animasi & interaksi

- Tirai transisi antar halaman (dan intro logo di kunjungan pertama)
- Judul muncul baris per baris dari balik mask
- Kartu karya terbuka dengan clip-path saat discroll, tilt 3D + parallax + kilau cahaya saat hover
- Kursor kustom yang membesar jadi "View" di atas karya
- Tombol magnetik, isian tombol yang naik saat hover
- Pil filter kategori yang meluncur, galeri berganti tanpa reload
- Lightbox detail karya: gambar "terbang" dari kartu ke layar penuh, navigasi ← → dan Esc
- Like dengan animasi hati + percikan partikel
- Angka statistik menghitung naik, progress bar terisi, garis tab meluncur
- Halaman Artists: gambar karya mengikuti kursor saat hover nama
- Navigasi menyembunyi saat scroll turun, garis progres scroll di bawah navigasi
- Menghormati pengaturan **Reduce motion** di sistem operasi

## Keamanan yang sudah diterapkan

- Query memakai PDO prepared statement (anti SQL injection)
- Semua output di-escape dengan `e()` (anti XSS)
- Token CSRF di setiap form dan request AJAX
- Password disimpan dengan `password_hash()`
- `session_regenerate_id()` saat login, cookie session `HttpOnly` + `SameSite`
- "Keep me signed in" memakai token selector/validator yang di-hash
- Upload dicek dari isi file (MIME + `getimagesize`), nama file acak, dan folder `uploads/` tidak bisa menjalankan PHP
- Folder `config/`, `includes/`, dan `sql/` diblokir dari browser lewat `.htaccess`

## Yang belum ada

- **Login Google**: tombolnya sudah ada, tapi perlu Client ID dari Google Cloud Console.
- **Lupa password**: butuh pengiriman email (misalnya PHPMailer + SMTP).
- **Gambar beresolusi tinggi**: gambar di `assets/img/` diambil dari Figma pada ukuran 1x. Untuk hasil lebih tajam, export ulang dari Figma pada 2x dengan nama file yang sama.

## Sebelum online (hosting)

- Ubah `APP_DEBUG` menjadi `false` di `config/config.php`.
- Pakai user database khusus, bukan `root`.
- Aktifkan HTTPS.
