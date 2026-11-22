<?php
/**
 * POST api/view.php (artwork_id)
 * Menambah jumlah views saat karya dibuka. Satu kali per sesi per karya.
 */
require __DIR__ . '/../includes/bootstrap.php';

if (!is_post() || !verify_csrf()) {
    json_response(['error' => 'Invalid request.'], 400);
}
$id = (int) ($_POST['artwork_id'] ?? 0);
$_SESSION['viewed'] ??= [];

if ($id > 0 && !in_array($id, $_SESSION['viewed'], true)) {
    // Pemilik karya tidak menambah views karyanya sendiri
    db_run("UPDATE artworks SET views = views + 1, updated_at = updated_at WHERE id = ? AND status = 'published' AND user_id <> ?", [$id, current_user_id() ?? 0]);
    $_SESSION['viewed'][] = $id;
}
$views = (int) db_value('SELECT views FROM artworks WHERE id = ?', [$id]);
json_response(['views' => $views]);
