<?php
/**
 * Daftar artis yang punya karya terpublikasi.
 */
require __DIR__ . '/includes/bootstrap.php';

$artists = db_all("SELECT u.id, u.username, u.display_name, u.bio,
                     COUNT(a.id) works, COALESCE(SUM(a.appreciations), 0) love,
                     (SELECT image_path FROM artworks x WHERE x.user_id = u.id AND x.status = 'published' ORDER BY x.appreciations DESC LIMIT 1) cover,
                     (SELECT c.name FROM artworks x JOIN categories c ON c.id = x.category_id WHERE x.user_id = u.id AND x.status = 'published' GROUP BY c.id, c.name ORDER BY COUNT(*) DESC LIMIT 1) main_category
                   FROM users u JOIN artworks a ON a.user_id = u.id AND a.status = 'published'
                   GROUP BY u.id ORDER BY love DESC");

page_start('Artists', 'page-artists');
site_nav('artists');
?>
<main id="main">
  <section class="hero hero--compact container">
    <div class="hero__left">
      <p class="eyebrow" data-intro style="--d:0">The people behind the work</p>
      <h1 class="hero__title" data-intro-lines>
        <span class="line"><span>Meet the artists.</span></span>
      </h1>
    </div>
    <div class="hero__right">
      <p class="lead" data-intro style="--d:3">Independent creatives sharing illustration, photography, design and more. Follow the ones that move you.</p>
    </div>
  </section>

  <section class="container">
    <ul class="artist-list">
      <?php foreach ($artists as $i => $a): ?>
        <li class="artist-row" data-reveal style="--i:<?= $i % 3 ?>">
          <a href="<?= url('profile.php', ['u' => $a['username']]) ?>" class="artist-row__link" data-cursor="view" data-cursor-label="Visit" data-hover-image="<?= e(image_url($a['cover'])) ?>">
            <span class="artist-row__index muted"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <span class="artist-row__name"><?= e($a['display_name']) ?></span>
            <span class="artist-row__cat muted"><?= e($a['main_category']) ?></span>
            <span class="artist-row__stats muted"><?= (int) $a['works'] ?> project<?= $a['works'] > 1 ? 's' : '' ?> · ♡ <?= num($a['love']) ?></span>
            <span class="artist-row__arrow arrow">↗</span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <!-- Gambar yang mengikuti kursor saat hover nama artis -->
  <div class="hover-reveal" aria-hidden="true" data-hover-reveal><img alt=""></div>

  <?php join_section(); ?>
</main>
<?php site_footer(); ?>
<?php page_end(); ?>
