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
