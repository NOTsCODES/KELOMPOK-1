<?php
/**
 * Dipanggil paling atas di setiap halaman: memuat config, koneksi DB,
 * helper, dan session login.
 */
require_once __DIR__ . '/../config/config.php';
date_default_timezone_set(APP_TIMEZONE);

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

// Session yang lebih aman
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_name('portofiles_session');
session_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

// Login otomatis dari cookie "Keep me signed in"
auth_try_remember_login();
