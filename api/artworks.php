<?php
/**
 * GET api/artworks.php?category=&sort=&q=&offset=
 * Mengembalikan HTML kartu karya untuk filter, pencarian, dan "Explore more".
 */
require __DIR__ . '/../includes/bootstrap.php';

$offset = (int) ($_GET['offset'] ?? 0);
$result = fetch_artworks([
    'category' => $_GET['category'] ?? 'for-you',
    'sort'     => $_GET['sort'] ?? 'curated',
    'q'        => $_GET['q'] ?? '',
    'offset'   => $offset,
    'limit'    => 6,
]);

json_response([
    'html'    => render_artwork_cards($result['items'], liked_ids(current_user_id(), $result['items']), $offset),
    'count'   => count($result['items']),
    'hasMore' => $result['hasMore'],
]);
