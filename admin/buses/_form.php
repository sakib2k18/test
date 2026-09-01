<?php
/**
 * Shared bus form, included by create.php and edit.php.
 * Expects: $bus (current values), $selectedFacilities, $formAction,
 *          $submitLabel, $isEdit, $errors, $allFacilities
 */
?>
<?= errorSummary($errors) ?>

<form class="form-card form js-validate" method="post"
      action="<?= e($formAction) ?>" enctype="multipart/form-data">
    <?= csrfField() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int) $bus['id'] ?>">
    <?php endif; ?>

    <!-- ---------------- Identity ---------------- -->
    <div class="form-section">
        <p class="form-section__title">Bus identity</p>

        <div class="form-row">
            <div class="field">
                <label class="field__label" for="bus_name">
                    Bus name / number <span class="req" aria-hidden="true">*</span>
                </label>
                <input class="input" type="text" id="bus_name" name="bus_name"
                       value="<?= e($bus['bus_name']) ?>" placeholder="e.g. KUET Bus-11"
                       data-label="Bus name" data-rule-required
                       data-rule-min="2" data-rule-max="80" required>
                <p class="field__error"></p>
            </div>

            <div class="field">
                <label class="field__label" for="reg_number">
                    Registration number <span class="req" aria-hidden="true">*</span>
                </label>
                <input class="input" type="text" id="reg_number" name="reg_number"
                       value="<?= e($bus['reg_number']) ?>" placeholder="e.g. KHU-METRO-JA-11-0461"
                       data-label="Registration number" data-rule-required
                       data-rule-min="3" data-rule-max="40" required>
                <p class="field__hint">Must be unique - no two buses can share a plate.</p>
                <p class="field__error"></p>
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label class="field__label" for="model">Model</label>
                <input class="input" type="text" id="model" name="model"
                       value="<?= e($bus['model']) ?>" placeholder="e.g. Ashok Leyland Viking"
                       data-label="Model" data-rule-max="80">
                <p class="field__error"></p>
            </div>

            <div class="field">
                <label class="field__label" for="capacity">
                    Seating capacity <span class="req" aria-hidden="true">*</span>
                </label>
                <input class="input" type="number" id="capacity" name="capacity"
                       value="<?= e($bus['capacity']) ?>" min="1" max="120" step="1"
                       data-label="Seating capacity" data-rule-required
                       data-rule-number="1,120" required>
                <p class="field__error"></p>
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label class="field__label" for="bus_type">
                    Bus type <span class="req" aria-hidden="true">*</span>
                </label>
                <select class="select" id="bus_type" name="bus_type"
                        data-label="Bus type" data-rule-required required>
                    <?php foreach (['Regular', 'Student Bus', 'Faculty Bus (AC)', 'Faculty Bus (Non AC)'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= $bus['bus_type'] === $opt ? 'selected' : '' ?>>
                            <?= e($opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="field__error"></p>
            </div>

            <div class="field">
                <label class="field__label" for="status">
                    Status <span class="req" aria-hidden="true">*</span>
                </label>
                <select class="select" id="status" name="status"
                        data-label="Status" data-rule-required required>
                    <?php foreach (['Active', 'Maintenance', 'Inactive'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= $bus['status'] === $opt ? 'selected' : '' ?>>
                            <?= e($opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="field__hint">Only "Active" buses are highlighted on the public site.</p>
                <p class="field__error"></p>
            </div>
        </div>
    </div>

    <!-- ---------------- Driver ---------------- -->
    <div class="form-section">
        <p class="form-section__title">Driver on duty</p>

        <div class="form-row">
            <div class="field">
                <label class="field__label" for="driver_name">Driver name</label>
                <input class="input" type="text" id="driver_name" name="driver_name"
                       value="<?= e($bus['driver_name']) ?>" placeholder="e.g. Md. Abdul Karim"
                       data-label="Driver name" data-rule-max="80">
                <p class="field__error"></p>
            </div>

            <div class="field">
                <label class="field__label" for="driver_contact">Driver mobile number</label>
                <input class="input" type="tel" id="driver_contact" name="driver_contact"
                       value="<?= e($bus['driver_contact']) ?>" placeholder="01712345678"
                       data-label="Driver mobile number" data-rule-phone>
                <p class="field__error"></p>
            </div>
        </div>
    </div>

    <!-- ---------------- Description & facilities ---------------- -->
    <div class="form-section">
        <p class="form-section__title">Description &amp; facilities</p>

        <div class="field">
            <label class="field__label" for="description">Description</label>
            <textarea class="textarea" id="description" name="description"
                      maxlength="2000" data-counter="descCount"
                      data-label="Description" data-rule-max="2000"
                      placeholder="Where this bus runs, its condition and anything passengers should know..."><?= e($bus['description']) ?></textarea>
            <p class="char-count" id="descCount">0 / 2000 characters</p>
            <p class="field__error"></p>
        </div>

        <div class="field">
            <span class="field__label">On-board facilities</span>
            <div class="check-grid">
                <?php foreach ($allFacilities as $facility): ?>
                    <label class="check-pill">
                        <input type="checkbox" name="facilities[]" value="<?= (int) $facility['id'] ?>"
                               <?= in_array((int) $facility['id'], $selectedFacilities, true) ? 'checked' : '' ?>>
                        <span><?= e($facility['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ---------------- Photo ---------------- -->
    <div class="form-section">
        <p class="form-section__title">Bus photo</p>

        <?php if ($isEdit && !isBlank($bus['image'])): ?>
            <div class="cluster mb-2" style="gap:16px;">
                <img src="<?= e(busImage($bus['image'])) ?>" alt="Current photo"
                     width="150" height="100"
                     style="width:150px;height:100px;object-fit:cover;border-radius:12px;border:1px solid var(--border);">
                <label class="check">
                    <input type="checkbox" name="remove_image" value="1">
                    <span>Remove the current photo and use the placeholder instead.</span>
                </label>
            </div>
        <?php endif; ?>

        <label class="file-drop" for="image">
            <?= icon('image') ?>
            <strong><?= $isEdit ? 'Upload a new photo' : 'Upload a photo of the bus' ?></strong>
            <span class="field__hint" style="display:block;margin-top:4px;">
                JPG, PNG or WEBP &middot; maximum 2 MB.
                <?= $isEdit ? 'Leave empty to keep the current photo.' : 'Optional - a placeholder is used when empty.' ?>
            </span>
            <input type="file" id="image" name="image"
                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                   data-preview="imagePreview"
                   data-label="Bus photo" data-rule-file="jpg,jpeg,png,webp" data-rule-filesize="2">
        </label>
        <p class="field__error"></p>

        <div class="image-preview" id="imagePreview">
            <img src="" alt="Preview of the selected photo">
        </div>
    </div>

    <!-- ---------------- Actions ---------------- -->
    <div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= icon('check') ?> <?= e($submitLabel) ?></button>
        <a class="btn btn--outline" href="<?= e(url('admin/buses/index.php')) ?>">Cancel</a>
    </div>
</form>
