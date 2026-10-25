<?php
/**
 * 活动表单（创建 / 编辑共用）
 * $form 数组需含 title, category_id, description, location, start_at, end_at, capacity
 */
$form = $form ?? [];
$errors = $errors ?? [];
$isEdit = $isEdit ?? false;
$formAction = $formAction ?? 'create-event.php';
$cancelUrl = $cancelUrl ?? 'my-events.php';
$coverUrl = $coverUrl ?? null;

$value = static fn(string $key): string => (string) ($form[$key] ?? '');
$categories = get_categories();
$years = ['Freshman', 'Sophomore', 'Junior', 'Senior', 'Graduate', 'Staff'];
?>
<?php if ($errors): ?>
    <div class="alert alert--error" role="alert">
        <?= icon('alert', 18) ?>
        <div>
            <strong>Please check the form</strong>
            <ul>
                <?php foreach ($errors as $message): ?>
                    <li><?= e($message) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<form method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data" class="form-card" novalidate>
    <?= csrf_field() ?>

    <section class="form-section">
        <div class="form-section__head">
            <h2>The basics</h2>
            <p>Give people a clear reason to show up.</p>
        </div>

        <label class="field">
            <span class="field__label">Event title</span>
            <input class="input" type="text" name="title" maxlength="90" required
                   placeholder="e.g. Midnight Pancake Run"
                   value="<?= e($value('title')) ?>">
            <span class="field__hint">Short and specific works best.</span>
        </label>

        <div class="field-grid">
            <label class="field">
                <span class="field__label">Category</span>
                <select class="input input--select" name="category_id" required>
                    <option value="">Choose a category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"<?= sel((string) $category['id'], $value('category_id')) ?>>
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field">
                <span class="field__label">Capacity</span>
                <input class="input" type="number" name="capacity" min="1" max="500" step="1" required
                       value="<?= e($value('capacity') !== '' ? $value('capacity') : '50') ?>">
                <span class="field__hint">How many people can join?</span>
            </label>
        </div>

        <label class="field">
            <span class="field__label">Location</span>
            <input class="input" type="text" name="location" maxlength="120" required
                   placeholder="e.g. Hodson Dining Commons"
                   value="<?= e($value('location')) ?>">
        </label>

        <div class="field-grid">
            <label class="field">
                <span class="field__label">Starts</span>
                <input class="input" type="datetime-local" name="start_at" required
                       value="<?= e($value('start_at')) ?>">
            </label>
            <label class="field">
                <span class="field__label">Ends</span>
                <input class="input" type="datetime-local" name="end_at" required
                       value="<?= e($value('end_at')) ?>">
            </label>
        </div>

        <label class="field">
            <span class="field__label">Description</span>
            <textarea class="input textarea" name="description" rows="8" maxlength="4000" required
                      placeholder="What will happen? Who is it for? What should people bring?"><?= e($value('description')) ?></textarea>
            <span class="field__hint">Line breaks are preserved on the event page.</span>
        </label>
    </section>

    <section class="form-section">
        <div class="form-section__head">
            <h2>Cover image</h2>
            <p>Optional. We will use a category photo if you skip this.</p>
        </div>

        <div class="cover-upload">
            <div class="cover-upload__preview" data-cover-preview>
                <?php if ($coverUrl): ?>
                    <img src="<?= e($coverUrl) ?>" alt="Current cover">
                <?php else: ?>
                    <span class="cover-upload__placeholder"><?= icon('upload', 30) ?><em>No cover yet</em></span>
                <?php endif; ?>
            </div>
            <div class="cover-upload__body">
                <label class="file-input">
                    <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" data-cover-input>
                    <span class="btn btn--ghost btn--sm"><?= icon('upload', 16) ?> Choose image</span>
                </label>
                <p class="field__hint">JPG, PNG or WEBP · up to 4 MB · 16:10 looks best.</p>
            </div>
        </div>
    </section>

    <div class="form-actions">
        <a class="btn btn--ghost" href="<?= e($cancelUrl) ?>">Cancel</a>
        <button class="btn btn--primary" type="submit">
            <?= icon($isEdit ? 'check' : 'sparkles', 17) ?>
            <?= e($isEdit ? 'Save changes' : 'Publish event') ?>
        </button>
    </div>
</form>
