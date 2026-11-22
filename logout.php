<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_post() && verify_csrf()) {
    logout_user();
    flash('You’re signed out. See you soon.', 'info');
}
redirect(url('index.php'));
