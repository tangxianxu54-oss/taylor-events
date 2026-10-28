<?php
/**
 * Workshop / Seminar 表单（创建 / 编辑共用）
 * $form 需含 title, topic_id, speaker, mode, location, start_at, end_at, capacity, description
 */
$form = $form ?? [];
$errors = $errors ?? [];
$isEdit = $isEdit ?? false;
$formAction = $formAction ?? 'create-workshop.php';
$cancelUrl = $cancelUrl ?? 'workshops.php';
$coverUrl = $coverUrl ?? null;

$value = static fn(string $key): string => (string) ($form[$key] ?? '');
$topics = get_categories();
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
            <h2>The workshop</h2>
            <p>What will students learn, and who is leading it?</p>
        </div>

        <label class="field">
            <span class="field__label">Workshop title</span>
            <input class="input" type="text" name="title" maxlength="100" required
                   placeholder="e.g. LinkedIn Profile Clinic"
                   value="<?= e($value('title')) ?>">
        </label>

        <div class="field-grid">
            <label class="field">
                <span class="field__label">Topic</span>
                <select class="input input--select" name="topic_id" required>
                    <option value="">Choose a topic</option>
                    <?php foreach ($topics as $topic): ?>
                        <option value="<?= (int) $topic['id'] ?>"<?= sel((string) $topic['id'], $value('topic_id')) ?>>
                            <?= e($topic['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field">
                <span class="field__label">Format</span>
                <select class="input input--select" name="mode" required>
                    <option value="in-person"<?= sel('in-person', $value('mode') !== '' ? $value('mode') : 'in-person') ?>>In person</option>
                    <option value="online"<?= sel('online', $value('mode')) ?>>Online</option>
                    <option value="hybrid"<?= sel('hybrid', $value('mode')) ?>>Hybrid</option>
                </select>
            </label>
        </div>

        <label class="field">
            <span class="field__label">Speaker / facilitator</span>
            <input class="input" type="text" name="speaker" maxlength="90" required
                   placeholder="e.g. Dr. Maya Chen, Career Services"
                   value="<?= e($value('speaker')) ?>">
        </label>

        <label class="field">
            <span class="field__label">Location or meeting link</span>
            <input class="input" type="text" name="location" maxlength="160" required
                   placeholder="Room 204, Library · or a Zoom link"
                   value="<?= e($value('location') !== '' ? $value('location') : ($isEdit ? '' : "Taylor's University")) ?>">
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

        <div class="field-grid">
            <label class="field">
                <span class="field__label">Seats</span>
                <input class="input" type="number" name="capacity" min="1" max="500" step="1" required
                       value="<?= e($value('capacity') !== '' ? $value('capacity') : '30') ?>">
                <span class="field__hint">Workshops work best small — 20 to 40 seats.</span>
            </label>
        </div>

        <label class="field">
            <span class="field__label">Description</span>
            <textarea class="input textarea" name="description" rows="8" maxlength="4000" required
                      placeholder="What will be covered? Who should come? What should they bring?"><?= e($value('description')) ?></textarea>
        </label>
    </section>

    <section class="form-section">
        <div class="form-section__head">
            <h2>Cover image</h2>
            <p>Optional. We will use a topic photo if you skip this.</p>
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
            <?= icon($isEdit ? 'check' : 'presentation', 17) ?>
            <?= e($isEdit ? 'Save changes' : 'Publish workshop') ?>
        </button>
    </div>
</form>
