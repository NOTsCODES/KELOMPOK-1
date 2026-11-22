<?php
require __DIR__ . '/includes/bootstrap.php';
page_start('About', 'page-about');
site_nav('about');
?>
<main id="main">
  <section class="hero container">
    <div class="hero__left">
      <p class="eyebrow" data-intro style="--d:0">About Portofiles</p>
      <h1 class="hero__title" data-intro-lines>
        <span class="line"><span>A home for original</span></span>
        <span class="line"><span>perspectives.</span></span>
      </h1>
    </div>
    <div class="hero__right">
      <p class="lead" data-intro style="--d:3">Portofiles is a quiet place on a loud internet. Independent creatives get a portfolio that looks good without the fuss, and everyone else gets a better way to find work worth loving.</p>
    </div>
  </section>

  <section class="container prose-grid">
    <article class="prose" id="guidelines" data-reveal>
      <p class="eyebrow">Community guidelines</p>
      <h2 class="h-section">Be kind. Share your own work.</h2>
      <ul>
        <li>Only upload work you made or have the right to share.</li>
        <li>Credit collaborators and references in your project story.</li>
        <li>Feedback is welcome; cruelty is not.</li>
        <li>No hateful, explicit or misleading content.</li>
      </ul>
    </article>
    <article class="prose" id="privacy" data-reveal style="--i:1">
      <p class="eyebrow">Privacy</p>
      <h2 class="h-section">Your work, your data.</h2>
      <ul>
        <li>We store your name, email, profile details and the projects you upload.</li>
        <li>Passwords are stored as one-way hashes, never as plain text.</li>
        <li>Drafts stay private until you publish them.</li>
        <li>You can delete any project at any time from your studio.</li>
      </ul>
    </article>
  </section>

  <?php join_section(); ?>
</main>
<?php site_footer(); ?>
<?php page_end(); ?>
