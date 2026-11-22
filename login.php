<?php
/**
 * Halaman Sign in.
 */
require __DIR__ . '/includes/bootstrap.php';

$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null, url('dashboard.php'));
if (current_user()) redirect($next);

$errors = [];
$email  = '';

if (is_post()) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    if (!verify_csrf()) {
        $errors['form'] = 'Your session expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif ($password === '') {
        $errors['password'] = 'Enter your password.';
    } else {
        // Batasi percobaan login (sederhana, per sesi)
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] > 8 && (time() - ($_SESSION['login_last'] ?? 0)) < 60) {
            $errors['form'] = 'Too many attempts. Please wait a minute.';
        } else {
            $_SESSION['login_last'] = time();
            $user = db_one('SELECT id, password_hash, display_name FROM users WHERE email = ?', [$email]);
            if ($user && password_verify($password, $user['password_hash'])) {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    db_run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
                }
                unset($_SESSION['login_attempts']);
                login_user((int) $user['id'], $remember);
                flash('Welcome back, ' . first_name($user['display_name']) . '.', 'success');
                redirect($next);
            }
            $errors['form'] = 'That email and password don’t match.';
        }
    }
}

page_start('Sign in', 'page-auth');
?>
<main class="auth">
  <?php include __DIR__ . '/includes/partials/auth-aside.php'; ?>

  <section class="auth__main">
    <div class="auth__top" data-intro style="--d:1">
      <a href="<?= url('index.php') ?>" class="auth__back"><span class="arrow arrow--left">←</span>&nbsp; Back to discover</a>
      <p><span class="muted">New here?</span>&nbsp; <a class="strong link-underline" href="<?= url('register.php', ['next' => $next]) ?>">Join Portofiles ↗</a></p>
    </div>

    <div class="auth__form-wrap">
      <h2 class="auth__title" data-intro-lines><span class="line"><span>Welcome back.</span></span></h2>
      <p class="auth__sub muted" data-intro style="--d:3">A little inspiration is waiting on the other side.</p>

      <?php if (!empty($errors['form'])): ?>
        <div class="alert" role="alert" data-intro style="--d:3"><?= e($errors['form']) ?></div>
      <?php endif; ?>

      <button class="btn btn--outline btn--block btn--xl" type="button" data-google data-intro style="--d:4">
        <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
        Continue with Google
      </button>

      <div class="divider-or" data-intro style="--d:5"><span>or</span></div>

      <form class="form" method="post" action="<?= url('login.php') ?>" novalidate data-auth-form>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">

        <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>" data-intro style="--d:6">
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" placeholder="you@example.com" value="<?= e($email) ?>" autocomplete="email" required>
          <?php if (isset($errors['email'])): ?><p class="field__error"><?= e($errors['email']) ?></p><?php endif; ?>
        </div>

        <div class="field<?= isset($errors['password']) ? ' has-error' : '' ?>" data-intro style="--d:7">
          <label for="password">Password</label>
          <div class="field__control">
            <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
            <button class="field__toggle" type="button" data-toggle-password aria-controls="password">Show</button>
          </div>
          <?php if (isset($errors['password'])): ?><p class="field__error"><?= e($errors['password']) ?></p><?php endif; ?>
        </div>

        <div class="form__row" data-intro style="--d:8">
          <label class="check"><input type="checkbox" name="remember" value="1"><span class="check__box" aria-hidden="true"></span>Keep me signed in</label>
          <a href="#" class="strong link-underline" data-forgot>Forgot password?</a>
        </div>

        <button class="btn btn--lime btn--block btn--xl" type="submit" data-intro style="--d:9" data-magnetic>
          <span class="btn__label">Sign in</span> <span class="arrow">→</span><span class="spinner" aria-hidden="true"></span>
        </button>
      </form>

      <p class="auth__terms muted" data-intro style="--d:10">By continuing, you agree to our <a href="<?= url('about.php') ?>#guidelines">Terms of service</a><br>and <a href="<?= url('about.php') ?>#privacy">Privacy policy</a>.</p>
    </div>

    <div class="auth__foot muted" data-intro style="--d:10">
      <span>© <?= date('Y') ?> Portofiles</span>
      <a href="mailto:hello@portofiles.art">Need a hand? Contact us</a>
    </div>
  </section>
</main>
<?php page_end(); ?>
