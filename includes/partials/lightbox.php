<?php /** Lightbox detail karya (dibuka dari kartu). */ ?>
<!-- ================= LIGHTBOX (detail karya) ================= -->
<div class="lightbox" data-lightbox aria-hidden="true" role="dialog" aria-modal="true" aria-label="Artwork detail">
  <div class="lightbox__backdrop" data-lightbox-close></div>
  <figure class="lightbox__stage">
    <img class="lightbox__img" alt="" data-lightbox-img>
  </figure>
  <aside class="lightbox__panel" data-lightbox-panel>
    <p class="eyebrow" data-lb-category></p>
    <h2 class="lightbox__title" data-lb-title></h2>
    <p class="lightbox__artist">by <a href="#" class="link-underline" data-lb-profile></a></p>
    <p class="lightbox__desc muted" data-lb-desc></p>
    <div class="lightbox__stats">
      <button class="like-btn like-btn--lg" type="button" data-lb-like><?= heart_icon() ?><span class="like-btn__count">0</span></button>
      <span class="muted"><span data-lb-views>0</span> views</span>
    </div>
    <div class="lightbox__nav">
      <button class="btn btn--outline btn--sm" type="button" data-lb-prev aria-label="Previous artwork">← Prev</button>
      <button class="btn btn--outline btn--sm" type="button" data-lb-next aria-label="Next artwork">Next →</button>
    </div>
  </aside>
  <button class="lightbox__close" type="button" data-lightbox-close aria-label="Close">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
  </button>
</div>
