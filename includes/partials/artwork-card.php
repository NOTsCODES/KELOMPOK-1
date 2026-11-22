<?php
/**
 * Satu kartu karya di grid.
 * Variabel yang tersedia: $art (array), $liked (bool), $index (int)
 */
$profileUrl = url('profile.php', ['u' => $art['username']]);
?>
<article class="art-card" data-reveal style="--i: <?= (int) ($index % 3) ?>"
  data-id="<?= (int) $art['id'] ?>"
  data-title="<?= e($art['title']) ?>"
  data-artist="<?= e($art['display_name']) ?>"
  data-category="<?= e($art['category_name']) ?>"
  data-desc="<?= e($art['description'] ?? '') ?>"
  data-image="<?= e(image_url($art['image_path'])) ?>"
  data-profile="<?= e($profileUrl) ?>"
  data-views="<?= (int) $art['views'] ?>">
  <button class="art-card__media" type="button" data-open-artwork data-cursor="view" aria-label="Open <?= e($art['title']) ?>">
    <span class="art-card__img-wrap">
      <img src="<?= e(image_url($art['image_path'])) ?>" alt="<?= e($art['title']) ?> by <?= e($art['display_name']) ?>" width="432" height="270" loading="lazy" decoding="async">
    </span>
    <span class="art-card__shine" aria-hidden="true"></span>
  </button>
  <div class="art-card__info">
    <div class="art-card__text">
      <h3 class="art-card__title"><?= e($art['title']) ?></h3>
      <p class="art-card__meta"><a href="<?= e($profileUrl) ?>" class="link-underline"><?= e($art['display_name']) ?></a><span class="sep">/</span><?= e($art['category_name']) ?></p>
    </div>
    <button class="like-btn<?= $liked ? ' is-liked' : '' ?>" type="button" data-like="<?= (int) $art['id'] ?>" aria-pressed="<?= $liked ? 'true' : 'false' ?>" aria-label="Appreciate <?= e($art['title']) ?>">
      <?= heart_icon() ?><span class="like-btn__count"><?= num($art['appreciations']) ?></span>
    </button>
  </div>
</article>
