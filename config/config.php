<?php
/**
 * Pengaturan utama Portofiles.
 * Sesuaikan bagian DATABASE dengan XAMPP kamu (default XAMPP: user root, password kosong).
 */

// ---------- DATABASE ----------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'portofiles');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---------- APLIKASI ----------
define('APP_NAME', 'Portofiles');

// Zona waktu (PHP & MySQL disamakan agar "2 hours ago" akurat)
define('APP_TIMEZONE', 'Asia/Jakarta');

// URL dasar proyek. Biarkan null agar terdeteksi otomatis
// (misal http://localhost/portofiles/ → '/portofiles').
// Isi manual kalau deteksi salah, contoh: define('BASE_PATH', '/portofiles');
define('BASE_PATH', null);

// Domain untuk tampilan link profil publik (hanya teks)
define('PUBLIC_DOMAIN', 'portofiles.art');

// Upload gambar
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_MAX_BYTES', 8 * 1024 * 1024); // 8 MB

// Tampilkan error PHP saat development. Ubah ke false saat online.
define('APP_DEBUG', true);
