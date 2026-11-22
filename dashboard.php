<?php
/**
 * Dashboard "Your studio": Overview, My projects, Saved work, Account settings.
 */
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();
$uid  = (int) $user['id'];

$views = ['overview', 'projects', 'saved', 'settings'];
$view  = in_array($_GET['view'] ?? '', $views, true) ? $_GET['view'] : 'overview';

/* ---------- Simpan pengaturan akun ---------- */
$errors = [];
if ($view === 'settings' && is_post()) {
    if (!verify_csrf()) {
        $errors['form'] = 'Your session expired. Please try again.';
    } elseif (($_POST['form'] ?? '') === 'profile') {
        $name    = trim($_POST['display_name'] ?? '');
        $bio     = trim($_POST['bio'] ?? '');
        $website = trim($_POST['website'] ?? '');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) $errors['display_name'] = 'Enter your name (2–80 characters).';
        if (mb_strlen($bio) > 400) $errors['bio'] = 'Keep your bio under 400 characters.';
        if ($website !== '' && !preg_match('~^(https?://)?[\w.-]+\.[a-z]{2,}(/\S*)?$~i', $website)) $errors['website'] = 'Enter a valid website.';
        if (!$errors) {
            db_run('UPDATE users SET display_name = ?, bio = ?, website = ? WHERE id = ?', [$name, $bio ?: null, $website ?: null, $uid]);
            flash('Profile saved.', 'success');
            redirect(url('dashboard.php', ['view' => 'settings']));
        }
    } elseif (($_POST['form'] ?? '') === 'password') {
        $hash = db_value('SELECT password_hash FROM users WHERE id = ?', [$uid]);
        $new  = $_POST['new_password'] ?? '';
        if (!password_verify($_POST['current_password'] ?? '', $hash)) $errors['current_password'] = 'Current password is incorrect.';
        if (strlen($new) < 8) $errors['new_password'] = 'Use at least 8 characters.';
        if (!$errors) {
            db_run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $uid]);
            db_run('DELETE FROM remember_tokens WHERE user_id = ?', [$uid]);
            flash('Password updated.', 'success');
            redirect(url('dashboard.php', ['view' => 'settings']));
        }
    }
}

/* ---------- Data ---------- */
$projects = db_all("SELECT * FROM artworks WHERE user_id = ? ORDER BY status = 'published' DESC, updated_at DESC", [$uid]);
$published = array_values(array_filter($projects, fn($p) => $p['status'] === 'published'));
$drafts    = array_values(array_filter($projects, fn($p) => $p['status'] === 'draft'));

$stats = [
    'views'          => array_sum(array_column($published, 'views')),
    'appreciations'  => array_sum(array_column($published, 'appreciations')),
    'followers'      => (int) db_value('SELECT COUNT(*) FROM follows WHERE following_id = ?', [$uid]),
    'likes30'        => (int) db_value('SELECT COUNT(*) FROM likes l JOIN artworks a ON a.id = l.artwork_id WHERE a.user_id = ? AND l.created_at >= NOW() - INTERVAL 30 DAY', [$uid]),
    'followers30'    => (int) db_value('SELECT COUNT(*) FROM follows WHERE following_id = ? AND created_at >= NOW() - INTERVAL 30 DAY', [$uid]),
];

// Aktivitas 7 hari terakhir
$activity = [];
foreach (db_all('SELECT a.title, COUNT(*) n, MAX(l.created_at) last_at FROM likes l JOIN artworks a ON a.id = l.artwork_id
                 WHERE a.user_id = ? AND l.created_at >= NOW() - INTERVAL 7 DAY GROUP BY a.id, a.title', [$uid]) as $r) {
    $activity[] = ['what' => $r['title'], 'detail' => $r['n'] . ' new appreciation' . ($r['n'] > 1 ? 's' : ''), 'at' => $r['last_at']];
}
$f = db_one('SELECT COUNT(*) n, MAX(created_at) last_at FROM follows WHERE following_id = ? AND created_at >= NOW() - INTERVAL 7 DAY', [$uid]);
if ($f && $f['n'] > 0) {
    $activity[] = ['what' => 'Your creative community', 'detail' => $f['n'] . ' new follower' . ($f['n'] > 1 ? 's' : ''), 'at' => $f['last_at']];
}
usort($activity, fn($a, $b) => strcmp($b['at'], $a['at']));
$activity = array_slice($activity, 0, 4);

$saved = [];
if ($view === 'saved') {
    $saved = db_all("SELECT a.*, u.display_name, u.username, c.name category_name FROM likes l
                     JOIN artworks a ON a.id = l.artwork_id JOIN users u ON u.id = a.user_id JOIN categories c ON c.id = a.category_id
                     WHERE l.user_id = ? AND a.status = 'published' ORDER BY l.created_at DESC", [$uid]);
}

$completion = profile_completion($user);
$firstName  = first_name($user['display_name']);
$publicUrl  = url('profile.php', ['u' => $user['username']]);

page_start('Your studio', 'page-dashboard');
site_nav('', 'account');
?>
<div class="studio">
  <!-- ================= SIDEBAR ================= -->
  <aside class="studio__sidebar">
    <div class="studio__sidebar-inner">
      <p class="eyebrow eyebrow--muted">Your studio</p>
      <nav class="side-nav" data-side-nav>
        <span class="side-nav__pill" aria-hidden="true" data-side-pill></span>
        <a href="<?= url('dashboard.php') ?>" class="<?= $view === 'overview' ? 'is-active' : '' ?>">Overview</a>
        <a href="<?= url('dashboard.php', ['view' => 'projects']) ?>" class="<?= $view === 'projects' ? 'is-active' : '' ?>">My projects <span class="side-nav__count"><?= count($projects) ?></span></a>
        <a href="<?= url('dashboard.php', ['view' => 'saved']) ?>" class="<?= $view === 'saved' ? 'is-active' : '' ?>">Saved work</a>
        <a href="<?= url('dashboard.php', ['view' => 'settings']) ?>" class="<?= $view === 'settings' ? 'is-active' : '' ?>">Account settings</a>
      </nav>

      <div class="portfolio-link" data-reveal>
        <p class="portfolio-link__title">Your little corner<br>of the internet.</p>
        <p class="muted portfolio-link__url" data-copy="<?= e((isset($_SERVER['HTTP_HOST']) ? '//' . $_SERVER['HTTP_HOST'] : '') . $publicUrl) ?>" title="Click to copy"><?= e(PUBLIC_DOMAIN . '/' . $user['username']) ?></p>
        <a class="strong link-underline" href="<?= $publicUrl ?>">View public profile&nbsp; <span class="arrow">↗</span></a>
      </div>

      <div class="studio__sidebar-foot">
        <a class="muted" href="mailto:hello@portofiles.art">Help &amp; feedback</a>
        <form action="<?= url('logout.php') ?>" method="post"><?= csrf_field() ?><button type="submit" class="strong">Log out</button></form>
      </div>
    </div>
  </aside>

  <main class="studio__main" id="main">
  <?php if ($view === 'overview'): ?>
    <!-- ================= OVERVIEW ================= -->
    <header class="studio__head">
      <div>
        <p class="eyebrow eyebrow--muted" data-intro style="--d:0">Your creative space</p>
        <h1 class="studio__title" data-intro-lines><span class="line"><span>Good to see you, <?= e($firstName) ?>.</span></span></h1>
        <p class="muted" data-intro style="--d:3">Here’s how your work is finding its people.</p>
      </div>
      <a class="btn btn--outline" href="<?= $publicUrl ?>" data-intro style="--d:3" data-magnetic>View public profile <span class="arrow">↗</span></a>
    </header>

    <section class="stats" aria-label="Portfolio stats">
      <div class="stat" data-reveal style="--i:0">
        <p class="stat__label">Published projects</p>
        <p class="stat__value" data-count="<?= count($published) ?>"><?= num(count($published)) ?></p>
        <p class="stat__trend muted"><?= count($drafts) ? count($drafts) . ' draft' . (count($drafts) > 1 ? 's' : '') . ' waiting in the wings' : 'No drafts waiting' ?></p>
      </div>
      <div class="stat" data-reveal style="--i:1">
        <p class="stat__label">Project views</p>
        <p class="stat__value" data-count="<?= $stats['views'] ?>"><?= num($stats['views']) ?></p>
        <p class="stat__trend muted">Across <?= count($published) ?> published project<?= count($published) === 1 ? '' : 's' ?></p>
      </div>
      <div class="stat" data-reveal style="--i:2">
        <p class="stat__label">Appreciations</p>
        <p class="stat__value" data-count="<?= $stats['appreciations'] ?>"><?= num($stats['appreciations']) ?></p>
        <p class="stat__trend<?= $stats['likes30'] ? ' is-up' : ' muted' ?>"><?= $stats['likes30'] ? '↑ ' . $stats['likes30'] : 'No new ones' ?> over the last 30 days</p>
      </div>
      <div class="stat" data-reveal style="--i:3">
        <p class="stat__label">Followers</p>
        <p class="stat__value" data-count="<?= $stats['followers'] ?>"><?= num($stats['followers']) ?></p>
        <p class="stat__trend<?= $stats['followers30'] ? ' is-up' : ' muted' ?>"><?= $stats['followers30'] ? '↑ ' . $stats['followers30'] : 'No new ones' ?> over the last 30 days</p>
      </div>
    </section>

    <section class="projects">
      <div class="projects__head" data-reveal>
        <h2 class="h-section h-section--sm">Your projects</h2>
        <a class="strong link-underline small" href="<?= url('dashboard.php', ['view' => 'projects']) ?>">Manage all projects&nbsp; <span class="arrow">→</span></a>
      </div>
      <?php render_project_tabs($projects, $published, $drafts); ?>
      <div class="project-grid" data-project-grid data-limit="3">
        <?php foreach ($projects as $i => $p) include __DIR__ . '/includes/partials/project-card.php'; ?>
        <?php if (!$projects) empty_projects(); ?>
      </div>
    </section>

    <section class="studio__bottom">
      <div class="panel" data-reveal style="--i:0">
        <div class="panel__head">
          <h2 class="panel__title">A little love for your work</h2>
          <span class="muted xsmall">Last 7 days</span>
        </div>
        <?php if ($activity): ?>
          <ul class="activity">
            <?php foreach ($activity as $k => $a): ?>
              <li style="--i:<?= $k ?>">
                <span class="strong-medium"><?= e($a['what']) ?></span>
                <span class="muted"><?= e($a['detail']) ?></span>
                <span class="muted xsmall"><?= e(day_label($a['at'])) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="muted activity__empty">A quiet week. Share something new and the love will follow.</p>
        <?php endif; ?>
      </div>

      <div class="panel panel--sage" data-reveal style="--i:1">
        <?php if ($completion < 100): ?>
          <p class="eyebrow">Make it yours</p>
          <h2 class="panel__title panel__title--lg">Let people meet the artist.</h2>
          <p class="muted small"><?= empty($user['bio']) ? 'Add a short bio to tell the story behind your work.' : 'Add a website so people can find more of your work.' ?></p>
        <?php else: ?>
          <p class="eyebrow">All set</p>
          <h2 class="panel__title panel__title--lg">Your profile is ready to shine.</h2>
          <p class="muted small">Keep your bio fresh as your work grows.</p>
        <?php endif; ?>
        <div class="progress" role="progressbar" aria-valuenow="<?= $completion ?>" aria-valuemin="0" aria-valuemax="100"><span data-progress="<?= $completion ?>"></span></div>
        <p class="muted xsmall">Your profile is <?= $completion ?>% complete</p>
        <a class="strong link-underline" href="<?= url('dashboard.php', ['view' => 'settings']) ?>">Edit your profile&nbsp; <span class="arrow">↗</span></a>
      </div>
    </section>

  <?php elseif ($view === 'projects'): ?>
    <!-- ================= MY PROJECTS ================= -->
    <header class="studio__head">
      <div>
        <p class="eyebrow eyebrow--muted" data-intro style="--d:0">Your studio</p>
        <h1 class="studio__title" data-intro-lines><span class="line"><span>My projects</span></span></h1>
        <p class="muted" data-intro style="--d:3">Everything you’ve made, published or still brewing.</p>
      </div>
      <a class="btn btn--lime" href="<?= url('upload.php') ?>" data-intro style="--d:3" data-magnetic>Upload a project <span class="arrow">↑</span></a>
    </header>
    <section class="projects projects--page">
      <?php render_project_tabs($projects, $published, $drafts); ?>
      <div class="project-grid" data-project-grid>
        <?php foreach ($projects as $i => $p) include __DIR__ . '/includes/partials/project-card.php'; ?>
        <?php if (!$projects) empty_projects(); ?>
      </div>
    </section>

  <?php elseif ($view === 'saved'): ?>
    <!-- ================= SAVED WORK ================= -->
    <header class="studio__head">
      <div>
        <p class="eyebrow eyebrow--muted" data-intro style="--d:0">Your studio</p>
        <h1 class="studio__title" data-intro-lines><span class="line"><span>Saved work</span></span></h1>
        <p class="muted" data-intro style="--d:3">Pieces you’ve appreciated, all in one place.</p>
      </div>
      <a class="btn btn--outline" href="<?= url('index.php') ?>" data-intro style="--d:3" data-magnetic>Discover more <span class="arrow">↗</span></a>
    </header>
    <?php if ($saved): ?>
      <div class="art-grid art-grid--studio"><?= render_artwork_cards($saved, array_map(fn($a) => (int) $a['id'], $saved)) ?></div>
    <?php else: ?>
      <div class="empty is-visible"><p class="h-section">Nothing saved yet.</p><p class="muted">Tap the heart on any piece to keep it here.</p></div>
    <?php endif; ?>

  <?php else: ?>
    <!-- ================= SETTINGS ================= -->
    <header class="studio__head">
      <div>
        <p class="eyebrow eyebrow--muted" data-intro style="--d:0">Your studio</p>
        <h1 class="studio__title" data-intro-lines><span class="line"><span>Account settings</span></span></h1>
        <p class="muted" data-intro style="--d:3">Shape how people meet you and your work.</p>
      </div>
    </header>
    <?php if (!empty($errors['form'])): ?><div class="alert"><?= e($errors['form']) ?></div><?php endif; ?>

    <div class="settings">
      <form class="panel form" method="post" action="<?= url('dashboard.php', ['view' => 'settings']) ?>" data-reveal>
        <?= csrf_field() ?><input type="hidden" name="form" value="profile">
        <h2 class="panel__title">Public profile</h2>
        <div class="field<?= isset($errors['display_name']) ? ' has-error' : '' ?>">
          <label for="display_name">Name</label>
          <input id="display_name" name="display_name" value="<?= e($_POST['display_name'] ?? $user['display_name']) ?>" required>
          <?php if (isset($errors['display_name'])): ?><p class="field__error"><?= e($errors['display_name']) ?></p><?php endif; ?>
        </div>
        <div class="field">
          <label>Portfolio link</label>
          <div class="field__control field__control--prefix is-readonly"><span class="field__prefix"><?= e(PUBLIC_DOMAIN) ?>/</span><input value="<?= e($user['username']) ?>" readonly></div>
        </div>
        <div class="field<?= isset($errors['bio']) ? ' has-error' : '' ?>">
          <label for="bio">Short bio <span class="muted xsmall" data-char-count="400"></span></label>
          <textarea id="bio" name="bio" rows="4" maxlength="400" placeholder="Tell the story behind your work."><?= e($_POST['bio'] ?? $user['bio']) ?></textarea>
          <?php if (isset($errors['bio'])): ?><p class="field__error"><?= e($errors['bio']) ?></p><?php endif; ?>
        </div>
        <div class="field<?= isset($errors['website']) ? ' has-error' : '' ?>">
          <label for="website">Website</label>
          <input id="website" name="website" value="<?= e($_POST['website'] ?? $user['website']) ?>" placeholder="yourstudio.com">
          <?php if (isset($errors['website'])): ?><p class="field__error"><?= e($errors['website']) ?></p><?php endif; ?>
        </div>
        <button class="btn btn--dark" type="submit" data-magnetic><span class="btn__label">Save profile</span><span class="spinner" aria-hidden="true"></span></button>
      </form>

      <form class="panel form" method="post" action="<?= url('dashboard.php', ['view' => 'settings']) ?>" data-reveal style="--i:1">
        <?= csrf_field() ?><input type="hidden" name="form" value="password">
        <h2 class="panel__title">Password</h2>
        <p class="muted small">Signed in as <?= e($user['email']) ?></p>
        <div class="field<?= isset($errors['current_password']) ? ' has-error' : '' ?>">
          <label for="current_password">Current password</label>
          <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
          <?php if (isset($errors['current_password'])): ?><p class="field__error"><?= e($errors['current_password']) ?></p><?php endif; ?>
        </div>
        <div class="field<?= isset($errors['new_password']) ? ' has-error' : '' ?>">
          <label for="new_password">New password</label>
          <div class="field__control">
            <input id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="8" required data-strength>
            <button class="field__toggle" type="button" data-toggle-password aria-controls="new_password">Show</button>
          </div>
          <div class="strength" aria-hidden="true"><span data-strength-bar></span></div>
          <?php if (isset($errors['new_password'])): ?><p class="field__error"><?= e($errors['new_password']) ?></p><?php endif; ?>
        </div>
        <button class="btn btn--outline" type="submit"><span class="btn__label">Update password</span><span class="spinner" aria-hidden="true"></span></button>
      </form>
    </div>
  <?php endif; ?>
  </main>
</div>
<?php include __DIR__ . '/includes/partials/lightbox.php'; ?>
<?php
page_end();

/* ---------- Helper khusus halaman ini ---------- */
function render_project_tabs(array $all, array $published, array $drafts): void
{
    ?>
    <div class="tabs" role="tablist" data-tabs>
      <button class="tab is-active" type="button" role="tab" aria-selected="true" data-filter="all">All projects <span><?= count($all) ?></span></button>
      <button class="tab" type="button" role="tab" aria-selected="false" data-filter="published">Published <span><?= count($published) ?></span></button>
      <button class="tab" type="button" role="tab" aria-selected="false" data-filter="draft">Drafts <span><?= count($drafts) ?></span></button>
      <span class="tabs__line" aria-hidden="true" data-tabs-line></span>
    </div>
    <?php
}

function empty_projects(): void
{
    ?>
    <div class="empty is-visible project-grid__empty">
      <p class="h-section">Your first piece starts here.</p>
      <p class="muted">Upload a project and give it a place to live.</p>
      <a class="btn btn--lime" href="<?= url('upload.php') ?>">Upload a project <span class="arrow">↑</span></a>
    </div>
    <?php
}
