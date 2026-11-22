<?php
/**
 * Halaman Discover (beranda): hero, filter kategori, dan galeri karya.
 */
require __DIR__ . '/includes/bootstrap.php';

$category = $_GET['category'] ?? 'for-you';
$sort     = isset(SORTS[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'curated';
$q        = trim($_GET['q'] ?? '');

$result  = fetch_artworks(['category' => $category, 'sort' => $sort, 'q' => $q, 'limit' => 6]);
$liked   = liked_ids(current_user_id(), $result['items']);
$user    = current_user();

page_start('', 'page-discover');
site_nav('discover', 'public', $q);
?>
<main id="main">
  <!-- ================= HERO ================= -->
  <section class="hero container">
    <div class="hero__left">
      <p class="eyebrow" data-intro style="--d:0">A space for creative expression</p>
      <h1 class="hero__title" data-intro-lines>
        <span class="line"><span>Good art deserves</span></span>
        <span class="line"><span>to be seen.</span></span>
      </h1>
      <p class="hero__aside muted" data-intro style="--d:6">A little inspiration goes a long way.</p>
    </div>
    <div class="hero__right">
      <p class="lead" data-intro style="--d:3">Discover independent creatives. Find your next<br class="br-desktop"> favorite piece. Give your own work a place to live.</p>
      <a class="btn btn--lime" href="<?= $user ? url('dashboard.php') : url('register.php') ?>" data-intro style="--d:4" data-magnetic>
        <?= $user ? 'Go to your studio' : 'Create your portfolio' ?> <span class="arrow">↗</span>
      </a>
      <p class="note" data-intro style="--d:5">Free to join. Open to everyone.</p>
    </div>
  </section>

  <!-- ================= FILTER ================= -->
  <section class="explore container" id="explore">
    <div class="explore__head" data-reveal>
      <h2 class="h-section">Explore the community</h2>
      <p class="muted explore__tagline" data-results-label>
        <?php if ($q !== ''): ?>
          Results for “<?= e($q) ?>”
        <?php else: ?>
          Fresh perspectives, thoughtfully selected.
        <?php endif; ?>
      </p>
    </div>

    <div class="filters" data-reveal>
      <div class="chips" role="tablist" aria-label="Categories" data-chips>
        <span class="chips__pill" aria-hidden="true" data-chips-pill></span>
        <button class="chip<?= $category === 'for-you' ? ' is-active' : '' ?>" type="button" role="tab" data-category="for-you" aria-selected="<?= $category === 'for-you' ? 'true' : 'false' ?>">For you</button>
        <?php foreach (categories() as $c): ?>
          <button class="chip<?= $category === $c['slug'] ? ' is-active' : '' ?>" type="button" role="tab" data-category="<?= e($c['slug']) ?>" aria-selected="<?= $category === $c['slug'] ? 'true' : 'false' ?>"><?= e($c['name']) ?></button>
        <?php endforeach; ?>
      </div>

      <div class="menu sort" data-menu>
        <button class="chip chip--sort" type="button" data-menu-toggle aria-expanded="false">
          <span data-sort-label><?= e(SORTS[$sort]['label']) ?></span> <span class="arrow arrow--down">↓</span>
        </button>
        <div class="menu__panel menu__panel--right" role="menu">
          <?php foreach (SORTS as $key => $s): ?>
            <button type="button" role="menuitemradio" data-sort="<?= e($key) ?>" aria-checked="<?= $key === $sort ? 'true' : 'false' ?>"><?= e($s['label']) ?></button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- ================= GALERI ================= -->
  <section class="gallery container" aria-live="polite">
    <div class="art-grid" data-gallery
         data-filter-category="<?= e($category) ?>" data-filter-sort="<?= e($sort) ?>" data-filter-q="<?= e($q) ?>"
         data-offset="<?= count($result['items']) ?>">
      <?= render_artwork_cards($result['items'], $liked) ?>
    </div>

    <div class="empty<?= $result['items'] ? '' : ' is-visible' ?>" data-empty>
      <p class="h-section">Nothing here yet.</p>
      <p class="muted">Try another category or search for something else.</p>
    </div>

    <div class="gallery__more">
      <button class="btn btn--outline btn--md<?= $result['hasMore'] ? '' : ' is-hidden' ?>" type="button" data-load-more data-magnetic>
        <span class="btn__label">Explore more work</span> <span class="arrow arrow--down">↓</span>
        <span class="spinner" aria-hidden="true"></span>
      </button>
      <p class="note" data-more-note><?= $result['hasMore'] ? 'A new perspective is always one scroll away.' : 'You’ve seen it all, for now.' ?></p>
    </div>
  </section>

  <?php join_section(); ?>
</main>
<?php site_footer(); ?>

<?php include __DIR__ . '/includes/partials/lightbox.php'; ?>
<?php page_end(); ?>
