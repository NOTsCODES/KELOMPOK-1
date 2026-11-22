<?php
/**
 * Halaman Join (daftar akun baru). Memakai layout yang sama dengan Sign in.
 */
require __DIR__ . '/includes/bootstrap.php';

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null, url('dashboard.php'));
if (current_user()) redirect($next);

$errors = [];
$old = ['display_name' => '', 'username' => '', 'email' => ''];

if (is_post()) {
    $old['display_name'] = trim($_POST['display_name'] ?? '');
    $old['username']     = strtolower(trim($_POST['username'] ?? ''));
    $old['email']        = trim($_POST['email'] ?? '');
    $password            = $_POST['password'] ?? '';

    if (!verify_csrf()) $errors['form'] = 'Your session expired. Please try again.';
    if (mb_strlen($old['display_name']) < 2 || mb_strlen($old['display_name']) > 80) $errors['display_name'] = 'Enter your name (2–80 characters).';
    if (!preg_match('/^[a-z0-9_]{3,30}$/', $old['username'])) $errors['username'] = 'Use 3–30 letters, numbers or underscores.';
    elseif (db_value('SELECT 1 FROM users WHERE username = ?', [$old['username']])) $errors['username'] = 'That username is taken.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
    elseif (db_value('SELECT 1 FROM users WHERE email = ?', [$old['email']])) $errors['email'] = 'An account with this email already exists.';
    if (strlen($password) < 8) $errors['password'] = 'Use at least 8 characters.';

    if (!$errors) {
        db_run('INSERT INTO users (username, email, password_hash, display_name) VALUES (?, ?, ?, ?)', [
            $old['username'], $old['email'], password_hash($password, PASSWORD_DEFAULT), $old['display_name'],
        ]);
        login_user((int) db()->lastInsertId());
        flash('Welcome to Portofiles, ' . first_name($old['display_name']) . '. Your studio is ready.', 'success');
        redirect($next);
    }
}

function field_error(array $errors, string $key): void
{
    if (isset($errors[$key])) echo '<p class="field__error">' . e($errors[$key]) . '</p>';
}

page_start('Join', 'page-auth');
?>
<main class="auth">
  <?php include __DIR__ . '/includes/partials/auth-aside.php'; ?>

  <section class="auth__main">
    <div class="auth__top" data-intro style="--d:1">
      <a href="<?= url('index.php') ?>" class="auth__back"><span class="arrow arrow--left">←</span>&nbsp; Back to discover</a>
      <p><span class="muted">Already a member?</span>&nbsp; <a class="strong link-underline" href="<?= url('login.php', ['next' => $next]) ?>">Sign in ↗</a></p>
    </div>

    <div class="auth__form-wrap">
      <h2 class="auth__title" data-intro-lines><span class="line"><span>Make it yours.</span></span></h2>
      <p class="auth__sub muted" data-intro style="--d:3">Create a free portfolio in less than a minute.</p>

      <?php if (!empty($errors['form'])): ?>
        <div class="alert" role="alert"><?= e($errors['form']) ?></div>
      <?php endif; ?>

      <form class="form" method="post" action="<?= url('register.php') ?>" novalidate data-auth-form>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">

        <div class="field<?= isset($errors['display_name']) ? ' has-error' : '' ?>" data-intro style="--d:4">
          <label for="display_name">Your name</label>
          <input id="display_name" name="display_name" type="text" placeholder="Maya Chen" value="<?= e($old['display_name']) ?>" autocomplete="name" required>
          <?php field_error($errors, 'display_name'); ?>
        </div>

        <div class="field<?= isset($errors['username']) ? ' has-error' : '' ?>" data-intro style="--d:5">
          <label for="username">Portfolio link</label>
          <div class="field__control field__control--prefix">
            <span class="field__prefix"><?= e(PUBLIC_DOMAIN) ?>/</span>
            <input id="username" name="username" type="text" placeholder="yourname" value="<?= e($old['username']) ?>" autocomplete="username" required pattern="[a-z0-9_]{3,30}">
          </div>
          <?php field_error($errors, 'username'); ?>
        </div>

        <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>" data-intro style="--d:6">
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" placeholder="you@example.com" value="<?= e($old['email']) ?>" autocomplete="email" required>
          <?php field_error($errors, 'email'); ?>
        </div>

        <div class="field<?= isset($errors['password']) ? ' has-error' : '' ?>" data-intro style="--d:7">
          <label for="password">Password</label>
          <div class="field__control">
            <input id="password" name="password" type="password" placeholder="At least 8 characters" autocomplete="new-password" required minlength="8" data-strength>
            <button class="field__toggle" type="button" data-toggle-password aria-controls="password">Show</button>
          </div>
          <div class="strength" aria-hidden="true"><span data-strength-bar></span></div>
          <?php field_error($errors, 'password'); ?>
        </div>

        <button class="btn btn--lime btn--block btn--xl" type="submit" data-intro style="--d:8" data-magnetic>
          <span class="btn__label">Create my portfolio</span> <span class="arrow">→</span><span class="spinner" aria-hidden="true"></span>
        </button>
      </form>

      <p class="auth__terms muted" data-intro style="--d:9">By continuing, you agree to our <a href="<?= url('about.php') ?>#guidelines">Terms of service</a><br>and <a href="<?= url('about.php') ?>#privacy">Privacy policy</a>.</p>
    </div>

    <div class="auth__foot muted" data-intro style="--d:10">
      <span>© <?= date('Y') ?> Portofiles</span>
      <a href="mailto:hello@portofiles.art">Need a hand? Contact us</a>
    </div>
  </section>
</main>
<?php page_end(); ?>
