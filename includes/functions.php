<?php
/* =====================================================================
 *  Helper umum
 * ===================================================================== */

/** Escape teks sebelum dicetak ke HTML (mencegah XSS). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Path dasar proyek, misal '/portofiles' atau '' jika di root domain. */
function base_path(): string
{
    static $base = null;
    if ($base !== null) return $base;
    if (BASE_PATH !== null) return $base = rtrim(BASE_PATH, '/');

    // Bandingkan lokasi file yang sedang jalan dengan URL-nya.
    // Contoh: file  C:/xampp/htdocs/portofiles/api/like.php → relatif "/api/like.php"
    //         URL   /portofiles/api/like.php               → base "/portofiles"
    $root   = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $script = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: '');
    $name   = $_SERVER['SCRIPT_NAME'] ?? '';
    $base   = '';
    if ($script !== '' && stripos($script, $root) === 0) {
        $rel = substr($script, strlen($root));
        if ($rel !== '' && str_ends_with($name, $rel)) {
            $base = substr($name, 0, -strlen($rel));
        }
    }
    return $base = rtrim($base, '/');
}

/** Bangun URL internal: url('login.php') → /portofiles/login.php */
function url(string $path = '', array $query = []): string
{
    $u = base_path() . '/' . ltrim($path, '/');
    if ($query) $u .= '?' . http_build_query($query);
    return $u;
}

/** URL aset dengan versi otomatis agar cache browser ter-update. */
function asset(string $path): string
{
    $file = __DIR__ . '/../' . ltrim($path, '/');
    $v = is_file($file) ? filemtime($file) : time();
    return url($path) . '?v=' . $v;
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

/** Hanya izinkan redirect ke path internal (mencegah open redirect). */
function safe_next(?string $next, string $fallback): string
{
    if ($next && str_starts_with($next, '/') && !str_starts_with($next, '//')) {
        return $next;
    }
    return $fallback;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------- Flash message (pesan sekali tampil) ---------- */
function flash(string $message, string $type = 'info'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && hash_equals(csrf_token(), $sent);
}

/* ---------- Format ---------- */
function num(int|float $n): string
{
    return number_format((float) $n);
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   { $m = intdiv($diff, 60);   return $m . ' minute' . ($m > 1 ? 's' : '') . ' ago'; }
    if ($diff < 86400)  { $h = intdiv($diff, 3600); return $h . ' hour' . ($h > 1 ? 's' : '') . ' ago'; }
    $d = intdiv($diff, 86400);
    if ($d === 1) return 'Yesterday';
    if ($d < 30)  return $d . ' days ago';
    return date('M j, Y', strtotime($datetime));
}

function day_label(string $datetime): string
{
    $d = (new DateTime(date('Y-m-d')))->diff(new DateTime(date('Y-m-d', strtotime($datetime))))->days;
    return match (true) {
        $d === 0 => 'Today',
        $d === 1 => 'Yesterday',
        default  => $d . ' days ago',
    };
}

function first_name(string $name): string
{
    return explode(' ', trim($name))[0];
}

/* =====================================================================
 *  Query karya (dipakai Discover, API load more, profil)
 * ===================================================================== */

const SORTS = [
    'curated'  => ['label' => 'Curated picks',   'sql' => 'a.is_curated DESC, a.published_at DESC'],
    'popular'  => ['label' => 'Most appreciated', 'sql' => 'a.appreciations DESC, a.published_at DESC'],
    'newest'   => ['label' => 'Newest',           'sql' => 'a.published_at DESC'],
];

function categories(): array
{
    static $cats = null;
    return $cats ??= db_all('SELECT id, slug, name FROM categories ORDER BY sort_order');
}

/**
 * Ambil karya yang sudah dipublikasi.
 * @return array{items: array, hasMore: bool}
 */
function fetch_artworks(array $opt = []): array
{
    $category = $opt['category'] ?? '';
    $q        = trim($opt['q'] ?? '');
    $sort     = isset(SORTS[$opt['sort'] ?? '']) ? $opt['sort'] : 'curated';
    $limit    = max(1, min(24, (int) ($opt['limit'] ?? 6)));
    $offset   = max(0, (int) ($opt['offset'] ?? 0));
    $userId   = $opt['user_id'] ?? null;

    $where  = ["a.status = 'published'"];
    $params = [];
    if ($category !== '' && $category !== 'for-you') {
        $where[] = 'c.slug = ?';
        $params[] = $category;
    }
    if ($q !== '') {
        $where[] = '(a.title LIKE ? OR u.display_name LIKE ? OR c.name LIKE ? OR a.description LIKE ?)';
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like);
    }
    if ($userId) {
        $where[] = 'a.user_id = ?';
        $params[] = (int) $userId;
    }

    $sql = 'SELECT a.*, u.display_name, u.username, c.name AS category_name, c.slug AS category_slug
            FROM artworks a
            JOIN users u ON u.id = a.user_id
            JOIN categories c ON c.id = a.category_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ' . SORTS[$sort]['sql'] . '
            LIMIT ' . ($limit + 1) . ' OFFSET ' . $offset;

    $rows = db_all($sql, $params);
    $hasMore = count($rows) > $limit;
    return ['items' => array_slice($rows, 0, $limit), 'hasMore' => $hasMore];
}

/** ID karya yang sudah di-like user (untuk menandai hati penuh). */
function liked_ids(?int $userId, array $artworks): array
{
    if (!$userId || !$artworks) return [];
    $ids = array_map(fn($a) => (int) $a['id'], $artworks);
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $rows = db_all("SELECT artwork_id FROM likes WHERE user_id = ? AND artwork_id IN ($in)", array_merge([$userId], $ids));
    return array_map('intval', array_column($rows, 'artwork_id'));
}

/** Render banyak kartu karya sekaligus (HTML). */
function render_artwork_cards(array $artworks, array $likedIds = [], int $startIndex = 0): string
{
    ob_start();
    foreach ($artworks as $i => $art) {
        $liked = in_array((int) $art['id'], $likedIds, true);
        $index = $startIndex + $i;
        include __DIR__ . '/partials/artwork-card.php';
    }
    return ob_get_clean();
}

/** URL gambar karya (bisa dari assets/ atau uploads/). */
function image_url(string $path): string
{
    return url($path);
}

/** Persentase kelengkapan profil (nama, username, bio, website). */
function profile_completion(array $user): int
{
    $fields = ['display_name', 'username', 'bio', 'website'];
    $filled = count(array_filter($fields, fn($f) => trim((string) ($user[$f] ?? '')) !== ''));
    return (int) round($filled / count($fields) * 100);
}
