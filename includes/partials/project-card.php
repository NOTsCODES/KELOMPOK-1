<?php
/** Kartu project milik user di dashboard. Variabel: $p (array), $i (int) */
$isDraft = $p['status'] === 'draft';
$editUrl = url('upload.php', ['id' => $p['id']]);
?>
<article class="project-card" data-status="<?= e($p['status']) ?>" data-reveal style="--i: <?= (int) ($i % 3) ?>">
  <a class="project-card__cover" href="<?= $editUrl ?>" data-cursor="view" data-cursor-label="Edit">
    <span class="art-card__img-wrap"><img src="<?= e(image_url($p['image_path'])) ?>" alt="<?= e($p['title']) ?>" loading="lazy" width="348" height="188"></span>
    <span class="badge<?= $isDraft ? ' badge--draft' : '' ?>"><?= $isDraft ? 'Draft' : 'Published' ?></span>
  </a>
  <div class="project-card__head">
    <h3 class="project-card__title"><?= e($p['title']) ?></h3>
    <div class="menu" data-menu>
      <button class="project-card__more" type="button" data-menu-toggle aria-expanded="false" aria-label="More actions for <?= e($p['title']) ?>">···</button>
      <div class="menu__panel menu__panel--right" role="menu">
        <a role="menuitem" href="<?= $editUrl ?>">Edit project</a>
        <?php if (!$isDraft): ?>
          <a role="menuitem" href="<?= url('profile.php', ['u' => current_user()['username']]) ?>">View on profile</a>
        <?php endif; ?>
        <form method="post" action="<?= url('actions.php') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <input type="hidden" name="action" value="<?= $isDraft ? 'publish' : 'unpublish' ?>">
          <button role="menuitem" type="submit"><?= $isDraft ? 'Publish now' : 'Move to drafts' ?></button>
        </form>
        <form method="post" action="<?= url('actions.php') ?>" data-confirm="Delete “<?= e($p['title']) ?>”? This can’t be undone.">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
          <input type="hidden" name="action" value="delete">
          <button role="menuitem" type="submit" class="danger">Delete</button>
        </form>
      </div>
    </div>
  </div>
  <p class="project-card__meta muted"><?php if ($isDraft): ?>Last edited <?= e(lcfirst(time_ago($p['updated_at']))) ?><?php else: ?><?= num($p['views']) ?> views&nbsp; · &nbsp;<?= num($p['appreciations']) ?> appreciations<?php endif; ?></p>
  <a class="project-card__link link-underline" href="<?= $editUrl ?>"><?= $isDraft ? 'Continue editing' : 'Edit project' ?>&nbsp; <span class="arrow">↗</span></a>
</article>
