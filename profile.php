<?php
/**
 * Profil publik artis: profile.php?u=username
 */
require __DIR__ . '/includes/bootstrap.php';

$username = strtolower(trim($_GET['u'] ?? ''));
$artist = db_one('SELECT id, username, display_name, bio, website, created_at FROM users WHERE username = ?', [$username]);
if (!$artist) {
    http_response_code(404);
    page_start('Not found');
    site_nav('artists');
    echo '<main class="container notfound"><p class="eyebrow">404</p><h1 class="hero__title">This corner is empty.</h1><p class="muted">We couldn’t find that artist.</p><a class="btn btn--dark" href="' . url('index.php') . '">Back to discover ↗</a></main>';
    site_footer();
    page_end();
    exit;
}

$me        = current_user();
$isMe      = $me && (int) $me['id'] === (int) $artist['id'];
$works     = fetch_artworks(['user_id' => $artist['id'], 'sort' => 'newest', 'limit' => 24])['items'];
$liked     = liked_ids(current_user_id(), $works);
$followers = (int) db_value('SELECT COUNT(*) FROM follows WHERE following_id = ?', [$artist['id']]);
$following = $me ? (bool) db_value('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?', [$me['id'], $artist['id']]) : false;
$totalLove = array_sum(array_column($works, 'appreciations'));
$website   = $artist['website'] ? (preg_match('~^https?://~', $artist['website']) ? $artist['website'] : 'https://' . $artist['website']) : null;

page_start($artist['display_name'], 'page-profile');
site_nav('artists');
?>
<main id="main">
  <section class="profile container">
    <div class="profile__avatar" data-intro style="--d:0" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($artist['display_name'], 0, 1))) ?></div>
    <div class="profile__main">
      <p class="eyebrow" data-intro style="--d:1"><?= e(PUBLIC_DOMAIN . '/' . $artist['username']) ?></p>
      <h1 class="hero__title hero__title--md" data-intro-lines><span class="line"><span><?= e($artist['display_name']) ?></span></span></h1>
      <p class="lead profile__bio" data-intro style="--d:3"><?= $artist['bio'] ? nl2br(e($artist['bio'])) : '<span class="muted">This artist is letting the work speak for itself.</span>' ?></p>
      <?php if ($website): ?>
        <a class="link-underline strong" href="<?= e($website) ?>" target="_blank" rel="noopener nofollow" data-intro style="--d:4"><?= e(preg_replace('~^https?://~', '', $artist['website'])) ?> ↗</a>
      <?php endif; ?>
    </div>
    <div class="profile__side" data-intro style="--d:4">
      <dl class="profile__stats">
        <div><dt class="muted">Projects</dt><dd data-count="<?= count($works) ?>"><?= count($works) ?></dd></div>
        <div><dt class="muted">Appreciations</dt><dd data-count="<?= $totalLove ?>"><?= num($totalLove) ?></dd></div>
        <div><dt class="muted">Followers</dt><dd data-follow-count><?= num($followers) ?></dd></div>
      </dl>
      <?php if ($isMe): ?>
        <a class="btn btn--outline" href="<?= url('dashboard.php', ['view' => 'settings']) ?>" data-magnetic>Edit profile <span class="arrow">↗</span></a>
      <?php else: ?>
        <button class="btn <?= $following ? 'btn--outline' : 'btn--dark' ?>" type="button" data-follow="<?= (int) $artist['id'] ?>" aria-pressed="<?= $following ? 'true' : 'false' ?>" data-magnetic>
          <span class="btn__label"><?= $following ? 'Following' : 'Follow' ?></span>
        </button>
      <?php endif; ?>
    </div>
  </section>

  <section class="gallery container">
    <div class="explore__head" data-reveal>
      <h2 class="h-section">Work</h2>
      <p class="muted">Member since <?= date('F Y', strtotime($artist['created_at'])) ?></p>
    </div>
    <?php if ($works): ?>
      <div class="art-grid"><?= render_artwork_cards($works, $liked) ?></div>
    <?php else: ?>
      <div class="empty is-visible"><p class="h-section">No published work yet.</p><p class="muted">Good things take time.</p></div>
    <?php endif; ?>
  </section>
  <?php if (!$me) join_section(); ?>
</main>
<?php site_footer(); ?>
<?php include __DIR__ . '/includes/partials/lightbox.php'; ?>
<?php page_end(); ?>
