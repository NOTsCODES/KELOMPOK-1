<div align="center">

# 🎨 Portofiles

**Tempat seniman memamerkan karya, dan orang menemukan inspirasi baru.**

Platform portofolio dan discovery karya seni yang dibangun dari desain Figma.
Ringan, tanpa framework, dan mudah dipelajari.

![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-PDO-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![CSS3](https://img.shields.io/badge/CSS3-Custom-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-Ready-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)

[Fitur](#-fitur) · [Mulai Cepat](#-mulai-cepat) · [Struktur](#-struktur-folder) · [Keamanan](#-keamanan) · [Rencana](#-rencana-berikutnya)

</div>

---

## ✨ Fitur

| | |
| --- | --- |
| 🖼️ **Galeri karya** | Jelajahi karya dengan filter kategori, urutan, pencarian, dan tombol *Explore more*, tanpa reload halaman. |
| 🔍 **Lightbox detail** | Lihat karya lebih besar, baca deskripsi, dan pindah antar karya dengan tombol atau panah ← →. Tutup dengan `Esc`. |
| ❤️ **Like & follow** | Beri apresiasi pada karya dan ikuti seniman favoritmu. |
| 👩‍🎨 **Profil publik** | Setiap seniman punya halaman portofolio sendiri (`profile.php?u=username`). |
| 📊 **Dashboard studio** | Ringkasan statistik, daftar project, karya tersimpan, dan pengaturan akun. |
| ⬆️ **Upload project** | Drag & drop gambar dengan preview. Simpan sebagai draft atau langsung publish. |
| 🔐 **Akun lengkap** | Daftar, login, "Keep me signed in", dan indikator kekuatan password. |
| 📱 **Responsif** | Tampilan menyesuaikan layar desktop, tablet, dan ponsel. |

> Antarmukanya sengaja dibuat bersih dan sederhana: tanpa tirai transisi, kursor kustom, atau efek hover yang ramai.

---

## 🚀 Mulai Cepat

Dibutuhkan **XAMPP** (Apache + MySQL + PHP). Tidak perlu Composer atau npm.

1. Salin folder `portofiles` ke `C:\xampp\htdocs\` (Windows) atau `/Applications/XAMPP/htdocs/` (macOS).
2. Buka **XAMPP Control Panel**, lalu **Start** Apache dan MySQL.
3. Buka `http://localhost/phpmyadmin`, pilih tab **Import**, pilih `sql/portofiles.sql`, lalu klik **Go**.
   File ini membuat database `portofiles` beserta data contohnya.
4. Buka **http://localhost/portofiles/** 🎉

Kalau user atau password MySQL kamu bukan `root` tanpa password, ubah di `config/config.php`.

### 🔑 Akun demo

| Email | Password |
| --- | --- |
| `maya@portofiles.test` | `portofiles123` |

Akun lain (`luca@`, `sofia@`, `noah@`, `amara@`, `yuki@` + `portofiles.test`) memakai password yang sama.

---

## 🧰 Teknologi

| Lapisan | Dipakai |
| --- | --- |
| Backend | PHP 8 dengan PDO dan prepared statement |
| Database | MySQL / MariaDB |
| Frontend | HTML, CSS, dan JavaScript murni (vanilla) |
| Font | Inter lewat Google Fonts |
| Server lokal | XAMPP |

---

## 🗂️ Struktur folder

```
portofiles/
├── index.php          Discover: hero, filter kategori, galeri, lightbox
├── login.php          Sign in
├── register.php       Join (daftar akun)
├── logout.php
├── dashboard.php      Studio: overview, my projects, saved work, settings
├── upload.php         Upload / edit project
├── actions.php        Publish, pindah ke draft, hapus project
├── profile.php        Profil publik seniman (?u=username)
├── artists.php        Daftar seniman
├── about.php          About, community guidelines, privacy
├── api/               Endpoint AJAX (JSON)
│   ├── artworks.php   Filter, sort, search, "Explore more"
│   ├── like.php       Like / appreciation
│   ├── follow.php     Follow seniman
│   └── view.php       Hitung views saat karya dibuka
├── config/config.php  Pengaturan database & aplikasi
├── includes/          Koneksi DB, helper, auth, layout, partial
├── assets/
│   ├── css/style.css
│   ├── js/main.js     Interaksi halaman
│   └── img/           Gambar karya dari Figma
├── uploads/           Gambar hasil upload user
└── sql/portofiles.sql Skema + data contoh
```

---

## 🛡️ Keamanan

- ✅ Query memakai **PDO prepared statement** (anti SQL injection)
- ✅ Semua output di-escape dengan `e()` (anti XSS)
- ✅ **Token CSRF** di setiap form dan request AJAX
- ✅ Password disimpan dengan `password_hash()`
- ✅ `session_regenerate_id()` saat login, cookie session `HttpOnly` + `SameSite`
- ✅ "Keep me signed in" memakai token selector/validator yang di-hash
- ✅ Upload dicek dari isi file (MIME + `getimagesize`), nama file diacak, dan folder `uploads/` tidak bisa menjalankan PHP
- ✅ Folder `config/`, `includes/`, dan `sql/` diblokir dari browser lewat `.htaccess`

---

## 🗺️ Rencana berikutnya

- [ ] **Lupa password**: butuh pengiriman email (misalnya PHPMailer + SMTP).
- [ ] **Login dengan Google**: perlu Client ID dari Google Cloud Console.
- [ ] **Gambar lebih tajam**: gambar di `assets/img/` diambil dari Figma pada 1x. Export ulang pada 2x dengan nama file yang sama.

---

## 🌐 Sebelum online (hosting)

- Ubah `APP_DEBUG` menjadi `false` di `config/config.php`.
- Pakai user database khusus, bukan `root`.
- Aktifkan HTTPS.

---

<div align="center">

Dibuat dengan ☕ dan rasa cinta pada karya seni.

</div>
