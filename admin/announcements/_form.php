<?php
/**
 * Shared announcement form, included by create.php and edit.php.
 * The matching server-side rules live in _validate.php.
 * Expects: $notice, $formAction, $submitLabel, $isEdit, $errors
 */
?>
<?= errorSummary($errors) ?>

<form class="form-card form js-validate" method="post" action="<?= e($formAction) ?>">
    <?= csrfField() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int) $notice['id'] ?>">
    <?php endif; ?>

    <div class="form-section">
        <p class="form-section__title">Notice</p>

        <div class="field">
            <label class="field__label" for="title">
                Title <span class="req" aria-hidden="true">*</span>
            </label>
            <input class="input" type="text" id="title" name="title"
                   value="<?= e($notice['title']) ?>" maxlength="160"
                   placeholder="e.g. Revised bus schedule from the new semester"
                   data-counter="titleCount"
                   data-label="Title" data-rule-required
                   data-rule-min="5" data-rule-max="160" required>
            <p class="char-count" id="titleCount">0 / 160 characters</p>
            <p class="field__error"></p>
        </div>

        <div class="field">
            <label class="field__label" for="short_description">
                Short description <span class="req" aria-hidden="true">*</span>
            </label>
            <textarea class="textarea" id="short_description" name="short_description"
                      maxlength="255" style="min-height:80px;"
                      data-counter="shortCount"
                      data-label="Short description" data-rule-required
                      data-rule-min="10" data-rule-max="255"
                      placeholder="One or two sentences shown on the notice board and the homepage."
                      required><?= e($notice['short_description']) ?></textarea>
            <p class="char-count" id="shortCount">0 / 255 characters</p>
            <p class="field__error"></p>
        </div>

        <div class="field">
            <label class="field__label" for="content">
                Full content <span class="req" aria-hidden="true">*</span>
            </label>
            <textarea class="textarea" id="content" name="content"
                      maxlength="8000" style="min-height:220px;"
                      data-counter="contentCount"
                      data-label="Full content" data-rule-required
                      data-rule-min="20" data-rule-max="8000"
                      placeholder="Write the complete notice here. Leave a blank line between paragraphs."
                      required><?= e($notice['content']) ?></textarea>
            <p class="char-count" id="contentCount">0 / 8000 characters</p>
            <p class="field__hint">Plain text only. A blank line starts a new paragraph on the public page.</p>
            <p class="field__error"></p>
        </div>
    </div>

    <div class="form-section">
        <p class="form-section__title">Publication</p>

        <div class="form-row">
            <div class="field">
                <label class="field__label" for="priority">
                    Priority <span class="req" aria-hidden="true">*</span>
                </label>
                <select class="select" id="priority" name="priority" data-label="Priority" required>
                    <?php foreach (['Normal', 'Important', 'Urgent'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= $notice['priority'] === $opt ? 'selected' : '' ?>>
                            <?= e($opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="field__hint">Urgent notices appear at the top of the notice board.</p>
                <p class="field__error"></p>
            </div>

            <div class="field">
                <label class="field__label" for="status">
                    Status <span class="req" aria-hidden="true">*</span>
                </label>
                <select class="select" id="status" name="status" data-label="Status" required>
                    <?php foreach (['Published', 'Draft'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= $notice['status'] === $opt ? 'selected' : '' ?>>
                            <?= e($opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="field__hint">Drafts are never visible on the public site.</p>
                <p class="field__error"></p>
            </div>

            <div class="field">
                <label class="field__label" for="published_on">
                    Publication date <span class="req" aria-hidden="true">*</span>
                </label>
                <input class="input" type="date" id="published_on" name="published_on"
                       value="<?= e($notice['published_on']) ?>"
                       data-label="Publication date" data-rule-required required>
                <p class="field__error"></p>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= icon('check') ?> <?= e($submitLabel) ?></button>
        <a class="btn btn--outline" href="<?= e(url('admin/announcements/index.php')) ?>">Cancel</a>
    </div>
</form>
