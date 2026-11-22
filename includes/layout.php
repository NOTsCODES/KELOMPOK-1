<?php
/* =====================================================================
 *  Layout bersama: <head>, navigasi, footer, dan penutup halaman.
 * ===================================================================== */

function page_start(string $title, string $bodyClass = ''): void
{
    $fullTitle = $title ? $title . ' — ' . APP_NAME : APP_NAME . ' — Good art deserves to be seen';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($fullTitle) ?></title>
  <meta name="description" content="Discover independent creatives. Find your next favorite piece. Give your own work a place to live.">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <meta name="base-path" content="<?= e(base_path()) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='8' fill='%2320211d'/><circle cx='16' cy='16' r='7' fill='%23e1ff62'/></svg>">
  <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
  <script>
    (function (d) {
      d.classList.add('js');
      try {
        var calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!calm && (sessionStorage.getItem('pf-nav') || !sessionStorage.getItem('pf-seen'))) d.classList.add('pf-cover');
      } catch (e) {}
    })(document.documentElement);
  </script>
</head>
<body class="<?= e($bodyClass) ?>">
  <!-- Tirai transisi antar halaman -->
  <div class="curtain" aria-hidden="true"><div class="curtain__panel"></div><span class="curtain__logo">portofiles</span></div>
  <!-- Kursor kustom (hanya mouse) -->
  <div class="cursor is-hidden" aria-hidden="true"><div class="cursor__ring"><span class="cursor__label">View</span></div><div class="cursor__dot"></div></div>
  <div class="toasts" aria-live="polite"></div>
    <?php
    foreach (take_flashes() as $f) {
        echo '<template class="flash" data-type="' . e($f['type']) . '">' . e($f['message']) . '</template>';
    }
}

function page_end(): void
{
    ?>
  <script src="<?= asset('assets/js/main.js') ?>" defer></script>
</body>
</html>
    <?php
}

function search_form(string $value = ''): void
{
    ?>
    <form class="search" action="<?= url('index.php') ?>" method="get" role="search">
      <svg class="search__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 5 5"/></svg>
      <input class="search__input" type="search" name="q" value="<?= e($value) ?>" placeholder="Search art, artists, inspiration" aria-label="Search art, artists, inspiration" autocomplete="off">
      <kbd class="search__kbd" aria-hidden="true">/</kbd>
    </form>
    <?php
}

function user_menu(array $user): void
{
    ?>
    <div class="menu" data-menu>
      <button class="nav__user" type="button" data-menu-toggle aria-expanded="false">
        <?= e($user['display_name']) ?> <span class="arrow arrow--down">↓</span>
      </button>
      <div class="menu__panel menu__panel--right" role="menu">
        <a role="menuitem" href="<?= url('dashboard.php') ?>">Overview</a>
        <a role="menuitem" href="<?= url('profile.php', ['u' => $user['username']]) ?>">Public profile</a>
        <a role="menuitem" href="<?= url('dashboard.php', ['view' => 'saved']) ?>">Saved work</a>
        <a role="menuitem" href="<?= url('dashboard.php', ['view' => 'settings']) ?>">Account settings</a>
        <form action="<?= url('logout.php') ?>" method="post"><?= csrf_field() ?><button role="menuitem" type="submit">Log out</button></form>
      </div>
    </div>
    <?php
}

/**
 * Navigasi atas.
 * $variant: 'public' (Discover) atau 'account' (dashboard).
 */
function site_nav(string $active = 'discover', string $variant = 'public', string $search = ''): void
{
    $user = current_user();
    ?>
  <header class="nav nav--<?= e($variant) ?>" data-nav>
    <div class="nav__inner container<?= $variant === 'account' ? ' container--full' : '' ?>">
      <a class="logo" href="<?= url('index.php') ?>" data-magnetic>portofiles</a>
      <nav class="nav__links" aria-label="Main">
        <a href="<?= url('index.php') ?>" class="<?= $active === 'discover' ? 'is-active' : '' ?>">Discover</a>
        <a href="<?= url('artists.php') ?>" class="<?= $active === 'artists' ? 'is-active' : '' ?>">Artists</a>
        <a href="<?= url('about.php') ?>" class="<?= $active === 'about' ? 'is-active' : '' ?>">About</a>
      </nav>
      <div class="nav__actions">
        <?php search_form($search); ?>
        <?php if ($variant === 'account' && $user): ?>
          <a class="btn btn--lime nav__cta" href="<?= url('upload.php') ?>" data-magnetic>Upload a project <span class="arrow">↑</span></a>
          <?php user_menu($user); ?>
        <?php elseif ($user): ?>
          <?php user_menu($user); ?>
          <a class="btn btn--dark nav__cta" href="<?= url('upload.php') ?>" data-magnetic>Share your work <span class="arrow">↗</span></a>
        <?php else: ?>
          <a class="nav__login" href="<?= url('login.php') ?>">Log in</a>
          <a class="btn btn--dark nav__cta" href="<?= url('upload.php') ?>" data-magnetic>Share your work <span class="arrow">↗</span></a>
        <?php endif; ?>
        <button class="nav__burger" type="button" aria-label="Open menu" data-burger><span></span><span></span></button>
      </div>
    </div>
    <div class="nav__divider container<?= $variant === 'account' ? ' container--flush' : '' ?>"><span class="nav__progress" data-scroll-progress></span></div>
  </header>
  <!-- Menu mobile -->
  <div class="mobile-menu" data-mobile-menu>
    <nav>
      <a href="<?= url('index.php') ?>">Discover</a>
      <a href="<?= url('artists.php') ?>">Artists</a>
      <a href="<?= url('about.php') ?>">About</a>
      <?php if ($user): ?>
        <a href="<?= url('dashboard.php') ?>">Your studio</a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>">Log in</a>
      <?php endif; ?>
      <a href="<?= url('upload.php') ?>">Share your work ↗</a>
    </nav>
  </div>
    <?php
}

function site_footer(): void
{
    ?>
  <footer class="footer">
    <div class="container footer__inner">
      <div class="footer__brand">
        <a class="logo logo--sm" href="<?= url('index.php') ?>">portofiles</a>
        <p class="muted">A home for original perspectives.</p>
      </div>
      <nav class="footer__links" aria-label="Footer">
        <a href="<?= url('about.php') ?>">About</a>
        <a href="<?= url('about.php') ?>#guidelines">Community guidelines</a>
        <a href="<?= url('about.php') ?>#privacy">Privacy</a>
      </nav>
      <p class="muted footer__copy">© <?= date('Y') ?> Portofiles</p>
    </div>
  </footer>
    <?php
}

/** Blok ajakan bergabung (dipakai beranda & halaman lain). */
function join_section(): void
{
    $user = current_user();
    $href = $user ? url('upload.php') : url('register.php');
    ?>
  <section class="join" data-reveal>
    <div class="container join__inner">
      <div>
        <p class="eyebrow" data-reveal>Your work belongs here</p>
        <h2 class="join__title">
          <span class="line"><span>Make a little space</span></span>
          <span class="line"><span>for your big ideas.</span></span>
        </h2>
      </div>
      <div class="join__side" data-reveal>
        <p class="lead">Build your portfolio, share what you love,<br>and find your creative community.</p>
        <a class="btn btn--dark btn--lg" href="<?= $href ?>" data-magnetic>Create a free portfolio <span class="arrow">↗</span></a>
        <p class="note">No fees. No gatekeeping. Just your creativity.</p>
      </div>
    </div>
  </section>
    <?php
}

/** Ikon hati kecil untuk tombol like. */
function heart_icon(): string
{
    return '<svg class="heart" width="13" height="13" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.4-9.3C1.3 7.9 3.4 4.5 6.9 4.5c2.1 0 3.6 1.1 5.1 3 1.5-1.9 3-3 5.1-3 3.5 0 5.6 3.4 4.3 6.7-1.9 4.7-9.4 9.3-9.4 9.3Z"/></svg>';
}
