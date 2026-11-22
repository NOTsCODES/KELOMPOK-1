<?php
/**
 * POST api/like.php  (artwork_id)
 * Toggle like / appreciation. Wajib login.
 */
require __DIR__ . '/../includes/bootstrap.php';

if (!is_post() || !verify_csrf()) {
    json_response(['error' => 'Invalid request.'], 400);
}
$user = current_user();
if (!$user) {
    json_response(['error' => 'Please log in to appreciate work.', 'login' => url('login.php')], 401);
}

$artworkId = (int) ($_POST['artwork_id'] ?? 0);
$art = db_one("SELECT id, user_id FROM artworks WHERE id = ? AND status = 'published'", [$artworkId]);
if (!$art) {
    json_response(['error' => 'Artwork not found.'], 404);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $exists = db_value('SELECT 1 FROM likes WHERE user_id = ? AND artwork_id = ?', [$user['id'], $artworkId]);
    if ($exists) {
        db_run('DELETE FROM likes WHERE user_id = ? AND artwork_id = ?', [$user['id'], $artworkId]);
        db_run('UPDATE artworks SET appreciations = GREATEST(appreciations - 1, 0), updated_at = updated_at WHERE id = ?', [$artworkId]);
        $liked = false;
    } else {
        db_run('INSERT INTO likes (user_id, artwork_id) VALUES (?, ?)', [$user['id'], $artworkId]);
        db_run('UPDATE artworks SET appreciations = appreciations + 1, updated_at = updated_at WHERE id = ?', [$artworkId]);
        $liked = true;
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_response(['error' => 'Could not save. Try again.'], 500);
}

$count = (int) db_value('SELECT appreciations FROM artworks WHERE id = ?', [$artworkId]);
json_response(['liked' => $liked, 'count' => $count]);
