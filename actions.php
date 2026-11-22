<?php
/**
 * Aksi project dari dashboard: publish, unpublish, delete.
 */
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$back = safe_next(parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH) . (($q = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_QUERY)) ? '?' . $q : ''), url('dashboard.php'));

if (!is_post() || !verify_csrf()) {
    flash('Something went wrong. Please try again.', 'error');
    redirect($back);
}

$id  = (int) ($_POST['id'] ?? 0);
$art = db_one('SELECT * FROM artworks WHERE id = ? AND user_id = ?', [$id, $user['id']]);
if (!$art) {
    flash('Project not found.', 'error');
    redirect($back);
}

switch ($_POST['action'] ?? '') {
    case 'publish':
        db_run("UPDATE artworks SET status = 'published', published_at = COALESCE(published_at, NOW()) WHERE id = ?", [$id]);
        flash('“' . $art['title'] . '” is live. Time to share it.', 'success');
        break;
    case 'unpublish':
        db_run("UPDATE artworks SET status = 'draft' WHERE id = ?", [$id]);
        flash('“' . $art['title'] . '” moved to drafts.', 'info');
        break;
    case 'delete':
        db_run('DELETE FROM artworks WHERE id = ?', [$id]);
        // Hapus file hanya jika hasil upload user
        if (str_starts_with($art['image_path'], 'uploads/')) {
            $file = __DIR__ . '/' . $art['image_path'];
            if (is_file($file)) unlink($file);
        }
        flash('Project deleted.', 'info');
        break;
}
redirect($back);
