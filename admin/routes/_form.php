<?php
/**
 * Shared route form, included by create.php and edit.php.
 * Expects: $route, $formAction, $submitLabel, $isEdit, $errors
 */
?>
<?= errorSummary($errors) ?>

<form class="form-card form js-validate" method="post" action="<?= e($formAction) ?>">
    <?= csrfField() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int) $route['id'] ?>">
    <?php endif; ?>

    <div class="form-section">
        <p class="form-section__title">Route identity</p>

        <div class="form-row">
            <div class="field">
                <label class="field__label" for="route_name">
                    Route name <span class="req" aria-hidden="true">*</span>
                </label>
                <input class="input" type="text" id="route_name" name="route_name"
                       value="<?= e($route['route_name']) ?>"
                       placeholder="e.g. KUET Campus - Sonadanga"
                       data-label="Route name" data-rule-required
                       data-rule-min="3" data-rule-max="120" required>
                <p class="field__error"></p>
            </div>

            <div class="field">
                <label class="field__label" for="route_code">
                    Route code <span class="req" aria-hidden="true">*</span>
                </label>
                <input class="input" type="text" id="route_code" name="route_code"
                       value="<?= e($route['route_code']) ?>" placeholder="e.g. K-08"
                       data-label="Route code" data-rule-required
                       data-rule-min="2" data-rule-max="20" required>
                <p class="field__hint">Short unique code - letters, numbers and dashes only.</p>
                <p class="field__error"></p>
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label class="field__label" for="start_point">
                    Starting point <span class="req" aria-hidden="true">*</span>
                </label>
                <input class="input" type="text" id="start_point" name="start_point"
                       value="<?= e($route['start_point']) ?>" placeholder="e.g. KUET Main Campus"
                       data-label="Starting point" data-rule-required data-rule-max="120" required>
                <p class="field__error"></p>
            </div>

            <div class="field">
                <label class="field__label" for="destination">
                    Destination <span class="req" aria-hidden="true">*</span>
                </label>
                <input class="input" type="text" id="destination" name="destination"
                       value="<?= e($route['destination']) ?>" placeholder="e.g. Sonadanga Bus Terminal"
                       data-label="Destination" data-rule-required data-rule-max="120" required>
                <p class="field__error"></p>
            </div>
        </div>
    </div>

    <div class="form-section">
        <p class="form-section__title">Journey details</p>

        <div class="form-row">
            <div class="field">
                <label class="field__label" for="distance_km">
                    Distance (km) <span class="req" aria-hidden="true">*</span>
                </label>
                <input class="input" type="number" id="distance_km" name="distance_km"
                       value="<?= e($route['distance_km']) ?>" min="0.1" max="500" step="0.1"
                       data-label="Distance" data-rule-required data-rule-number="0.1,500" required>
                <p class="field__error"></p>
            </div>

            <div class="field">
                <label class="field__label" for="duration_min">
                    Estimated duration (minutes) <span class="req" aria-hidden="true">*</span>
                </label>
                <input class="input" type="number" id="duration_min" name="duration_min"
                       value="<?= e($route['duration_min']) ?>" min="1" max="600" step="1"
                       data-label="Duration" data-rule-required data-rule-number="1,600" required>
                <p class="field__error"></p>
            </div>

            <div class="field">
                <label class="field__label" for="status">
                    Status <span class="req" aria-hidden="true">*</span>
                </label>
                <select class="select" id="status" name="status" data-label="Status" required>
                    <?php foreach (['Active', 'Inactive'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= $route['status'] === $opt ? 'selected' : '' ?>>
                            <?= e($opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="field__error"></p>
            </div>
        </div>

        <div class="field">
            <label class="field__label" for="description">Description</label>
            <textarea class="textarea" id="description" name="description"
                      maxlength="2000" data-counter="routeDescCount"
                      data-label="Description" data-rule-max="2000"
                      placeholder="Which areas the route passes through and who normally uses it..."><?= e($route['description']) ?></textarea>
            <p class="char-count" id="routeDescCount">0 / 2000 characters</p>
            <p class="field__error"></p>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= icon('check') ?> <?= e($submitLabel) ?></button>
        <a class="btn btn--outline" href="<?= e(url('admin/routes/index.php')) ?>">Cancel</a>
    </div>

    <?php if (!$isEdit): ?>
        <p class="field__hint" style="margin-top:6px;">
            After saving you will be taken to the route page, where you can add its stoppages.
        </p>
    <?php endif; ?>
</form>
