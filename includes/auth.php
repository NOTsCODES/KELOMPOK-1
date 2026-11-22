<?php
/* =====================================================================
 *  Autentikasi: login, logout, "Keep me signed in"
 * ===================================================================== */

const REMEMBER_COOKIE = 'portofiles_remember';
const REMEMBER_DAYS   = 30;

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) return $user;
    if (empty($_SESSION['user_id'])) return $user = null;
    $user = db_one('SELECT id, username, email, display_name, bio, website, created_at FROM users WHERE id = ?', [$_SESSION['user_id']]);
    if (!$user) unset($_SESSION['user_id']);
    return $user;
}

function current_user_id(): ?int
{
    return current_user()['id'] ?? null;
}

/** Wajib login. Jika belum, arahkan ke login lalu kembali ke halaman ini. */
function require_login(): array
{
    $u = current_user();
    if (!$u) {
        redirect(url('login.php', ['next' => $_SERVER['REQUEST_URI'] ?? url('dashboard.php')]));
    }
    return $u;
}

function login_user(int $userId, bool $remember = false): void
{
    session_regenerate_id(true); // cegah session fixation
    $_SESSION['user_id'] = $userId;
    if ($remember) {
        remember_issue($userId);
    }
}

function logout_user(): void
{
    if (!empty($_COOKIE[REMEMBER_COOKIE])) {
        [$selector] = explode(':', $_COOKIE[REMEMBER_COOKIE]) + [null];
        if ($selector) db_run('DELETE FROM remember_tokens WHERE selector = ?', [$selector]);
        setcookie(REMEMBER_COOKIE, '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    }
    $_SESSION = [];
    session_regenerate_id(true);
}

/* ---------- Remember me: cookie berisi selector:validator ---------- */
function remember_issue(int $userId): void
{
    $selector  = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $expires   = time() + REMEMBER_DAYS * 86400;
    db_run('INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)', [
        $userId, $selector, hash('sha256', $validator), date('Y-m-d H:i:s', $expires),
    ]);
    setcookie(REMEMBER_COOKIE, $selector . ':' . $validator, [
        'expires'  => $expires,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

function auth_try_remember_login(): void
{
    if (!empty($_SESSION['user_id']) || empty($_COOKIE[REMEMBER_COOKIE])) return;

    $parts = explode(':', $_COOKIE[REMEMBER_COOKIE]);
    if (count($parts) !== 2) return;
    [$selector, $validator] = $parts;

    $row = db_one('SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > NOW()', [$selector]);
    if ($row && hash_equals($row['validator_hash'], hash('sha256', $validator))) {
        // Token sekali pakai: hapus lalu terbitkan yang baru
        db_run('DELETE FROM remember_tokens WHERE id = ?', [$row['id']]);
        login_user((int) $row['user_id'], true);
    }
}
