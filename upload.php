<?php
/**
 * Upload project baru atau edit project (upload.php?id=123).
 */
require __DIR__ . '/includes/bootstrap.php';
$user = require_login();

$id   = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$art  = $id ? db_one('SELECT * FROM artworks WHERE id = ? AND user_id = ?', [$id, $user['id']]) : null;
if ($id && !$art) {
    flash('Project not found.', 'error');
    redirect(url('dashboard.php', ['view' => 'projects']));
}
$isEdit = (bool) $art;

$errors = [];
$data = [
    'title'       => $art['title'] ?? '',
    'category_id' => $art['category_id'] ?? '',
    'description' => $art['description'] ?? '',
];

if (is_post()) {
    $data['title']       = trim($_POST['title'] ?? '');
    $data['category_id'] = (int) ($_POST['category_id'] ?? 0);
    $data['description'] = trim($_POST['description'] ?? '');
    $publish             = ($_POST['intent'] ?? '') === 'publish';

    if (!verify_csrf()) $errors['form'] = 'Your session expired. Please try again.';
    if (mb_strlen($data['title']) < 2 || mb_strlen($data['title']) > 120) $errors['title'] = 'Give your project a title (2–120 characters).';
    if (!in_array($data['category_id'], array_map('intval', array_column(categories(), 'id')), true)) $errors['category_id'] = 'Choose a category.';
    if (mb_strlen($data['description']) > 1000) $errors['description'] = 'Keep the description under 1,000 characters.';

    // ---------- Validasi & simpan gambar ----------
    $newImage = null;
    $file = $_FILES['image'] ?? null;
    $hasFile = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if (!$hasFile && !$isEdit) {
        $errors['image'] = 'Add a cover image for your project.';
    } elseif ($hasFile && !$errors) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['image'] = $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
                ? 'That image is too large.' : 'Upload failed. Please try again.';
        } elseif ($file['size'] > UPLOAD_MAX_BYTES) {
            $errors['image'] = 'Images must be under ' . (UPLOAD_MAX_BYTES / 1024 / 1024) . ' MB.';
        } else {
            // Cek isi file sungguhan, bukan sekadar ekstensi
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $exts = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            if (!isset($exts[$mime]) || !@getimagesize($file['tmp_name'])) {
                $errors['image'] = 'Use a JPG, PNG, WebP or GIF image.';
            } else {
                if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $exts[$mime];
                if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) {
                    $newImage = 'uploads/' . $name;
                } else {
                    $errors['image'] = 'Could not save the image. Check that the uploads folder is writable.';
                }
            }
        }
    }

    if (!$errors) {
        if ($isEdit) {
            $status = $publish ? 'published' : (($_POST['intent'] ?? '') === 'draft' ? 'draft' : $art['status']);
            db_run('UPDATE artworks SET title = ?, category_id = ?, description = ?, image_path = ?, status = ?,
                    published_at = IF(? = "published", COALESCE(published_at, NOW()), published_at) WHERE id = ?', [
                $data['title'], $data['category_id'], $data['description'] ?: null,
                $newImage ?? $art['image_path'], $status, $status, $art['id'],
            ]);
            if ($newImage && str_starts_with($art['image_path'], 'uploads/') && is_file(__DIR__ . '/' . $art['image_path'])) {
                unlink(__DIR__ . '/' . $art['image_path']);
            }
        } else {
            db_run('INSERT INTO artworks (user_id, category_id, title, description, image_path, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?)', [
                $user['id'], $data['category_id'], $data['title'], $data['description'] ?: null, $newImage,
                $publish ? 'published' : 'draft', $publish ? date('Y-m-d H:i:s') : null,
            ]);
        }
        flash($publish ? '“' . $data['title'] . '” is live. Good art deserves to be seen.' : 'Saved to drafts.', 'success');
        redirect(url('dashboard.php', ['view' => 'projects']));
    } elseif ($newImage) {
        unlink(UPLOAD_DIR . '/' . basename($newImage)); // batalkan file jika form gagal
    }
}

page_start($isEdit ? 'Edit project' : 'Upload a project', 'page-upload');
site_nav('', 'account');
?>
<main class="upload container" id="main">
  <header class="studio__head">
    <div>
      <p class="eyebrow eyebrow--muted" data-intro style="--d:0"><a href="<?= url('dashboard.php', ['view' => 'projects']) ?>" class="link-underline">← Your projects</a></p>
      <h1 class="studio__title" data-intro-lines><span class="line"><span><?= $isEdit ? 'Edit “' . e($art['title']) . '”' : 'Share something new.' ?></span></span></h1>
      <p class="muted" data-intro style="--d:3"><?= $isEdit ? 'Polish the details, then send it back into the world.' : 'Every portfolio starts with one piece. This could be it.' ?></p>
    </div>
  </header>

  <?php if (!empty($errors['form'])): ?><div class="alert"><?= e($errors['form']) ?></div><?php endif; ?>

  <form class="upload__form" method="post" enctype="multipart/form-data" action="<?= url('upload.php') ?>" novalidate data-upload-form>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $art['id'] ?>"><?php endif; ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?= UPLOAD_MAX_BYTES ?>">

    <!-- Dropzone -->
    <div class="dropzone<?= $isEdit ? ' has-image' : '' ?><?= isset($errors['image']) ? ' has-error' : '' ?>" data-dropzone data-intro style="--d:4">
      <input class="dropzone__input" type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp,image/gif" data-dropzone-input>
      <label for="image" class="dropzone__label">
        <span class="dropzone__icon" aria-hidden="true">↑</span>
        <span class="dropzone__title">Drop your cover image here</span>
        <span class="muted small">or click to browse · JPG, PNG, WebP or GIF · up to <?= UPLOAD_MAX_BYTES / 1024 / 1024 ?> MB</span>
      </label>
      <img class="dropzone__preview" alt="" data-dropzone-preview <?= $isEdit ? 'src="' . e(image_url($art['image_path'])) . '"' : '' ?>>
      <button class="dropzone__change btn btn--sm" type="button" data-dropzone-change>Replace image</button>
      <?php if (isset($errors['image'])): ?><p class="field__error"><?= e($errors['image']) ?></p><?php endif; ?>
    </div>

    <div class="upload__fields">
      <div class="field<?= isset($errors['title']) ? ' has-error' : '' ?>" data-intro style="--d:5">
        <label for="title">Title</label>
        <input id="title" name="title" value="<?= e($data['title']) ?>" placeholder="Daydream in bloom" required maxlength="120">
        <?php if (isset($errors['title'])): ?><p class="field__error"><?= e($errors['title']) ?></p><?php endif; ?>
      </div>

      <fieldset class="field<?= isset($errors['category_id']) ? ' has-error' : '' ?>" data-intro style="--d:6">
        <legend>Category</legend>
        <div class="chips chips--radio">
          <?php foreach (categories() as $c): ?>
            <label class="chip chip--radio">
              <input type="radio" name="category_id" value="<?= (int) $c['id'] ?>" <?= (int) $data['category_id'] === (int) $c['id'] ? 'checked' : '' ?>>
              <span><?= e($c['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <?php if (isset($errors['category_id'])): ?><p class="field__error"><?= e($errors['category_id']) ?></p><?php endif; ?>
      </fieldset>

      <div class="field<?= isset($errors['description']) ? ' has-error' : '' ?>" data-intro style="--d:7">
        <label for="description">The story behind it <span class="muted xsmall" data-char-count="1000"></span></label>
        <textarea id="description" name="description" rows="5" maxlength="1000" placeholder="What inspired it? How did you make it?"><?= e($data['description']) ?></textarea>
        <?php if (isset($errors['description'])): ?><p class="field__error"><?= e($errors['description']) ?></p><?php endif; ?>
      </div>

      <div class="upload__actions" data-intro style="--d:8">
        <button class="btn btn--lime btn--lg" type="submit" name="intent" value="publish" data-magnetic><span class="btn__label"><?= $isEdit && $art['status'] === 'published' ? 'Save & keep published' : 'Publish project' ?></span> <span class="arrow">↗</span><span class="spinner" aria-hidden="true"></span></button>
        <button class="btn btn--outline btn--lg" type="submit" name="intent" value="draft"><span class="btn__label"><?= $isEdit && $art['status'] === 'published' ? 'Move to drafts' : 'Save as draft' ?></span><span class="spinner" aria-hidden="true"></span></button>
      </div>
    </div>
  </form>
</main>
<?php page_end(); ?>
