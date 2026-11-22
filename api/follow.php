<?php
/**
 * POST api/follow.php (user_id) — toggle follow artis. Wajib login.
 */
require __DIR__ . '/../includes/bootstrap.php';

if (!is_post() || !verify_csrf()) {
    json_response(['error' => 'Invalid request.'], 400);
}
$me = current_user();
if (!$me) {
    json_response(['error' => 'Please log in to follow artists.', 'login' => url('login.php')], 401);
}
$target = (int) ($_POST['user_id'] ?? 0);
if ($target === (int) $me['id'] || !db_value('SELECT 1 FROM users WHERE id = ?', [$target])) {
    json_response(['error' => 'Invalid artist.'], 400);
}

if (db_value('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?', [$me['id'], $target])) {
    db_run('DELETE FROM follows WHERE follower_id = ? AND following_id = ?', [$me['id'], $target]);
    $following = false;
} else {
    db_run('INSERT INTO follows (follower_id, following_id) VALUES (?, ?)', [$me['id'], $target]);
    $following = true;
}
$count = (int) db_value('SELECT COUNT(*) FROM follows WHERE following_id = ?', [$target]);
json_response(['following' => $following, 'count' => $count]);
