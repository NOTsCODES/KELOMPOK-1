<?php
/** Panel kiri halaman Sign in / Join: karya unggulan dengan efek parallax. */
?>
<aside class="auth__aside">
  <a class="logo" href="<?= url('index.php') ?>" data-intro style="--d:0">portofiles</a>
  <div class="auth__aside-body">
    <p class="eyebrow" data-intro style="--d:1">A home for original perspectives</p>
    <h1 class="auth__headline" data-intro-lines>
      <span class="line"><span>Your next chapter</span></span>
      <span class="line"><span>starts with a little art.</span></span>
    </h1>
    <figure class="auth__feature" data-parallax-frame>
      <div class="auth__feature-media" data-reveal-mask>
        <img src="<?= asset('assets/img/daydream-in-bloom-featured.jpg') ?>" alt="Daydream in bloom by Maya Chen" width="584" height="458" data-parallax-img>
      </div>
      <figcaption data-intro style="--d:6">
        <strong>Daydream in bloom</strong>
        <span class="muted">Maya Chen&nbsp; / &nbsp;Illustration</span>
      </figcaption>
    </figure>
  </div>
  <p class="auth__aside-foot" data-intro style="--d:7">Made to be shared. Just like your work.</p>
</aside>
