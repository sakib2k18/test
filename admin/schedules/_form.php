<?php
/**
 * Shared schedule form, included by create.php and edit.php.
 * Expects: $schedule, $allBuses, $allRoutes, $selectedDays,
 *          $formAction, $submitLabel, $isEdit, $errors
 */
$weekDays = explode(',', OPERATING_DAYS);
?>
<?= errorSummary($errors) ?>

<form class="form-card form js-validate" method="post" action="<?= e($formAction) ?>">
    <?= csrfField() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int) $schedule['id'] ?>">
    <?php endif; ?>

    <div class="form-section">
        <p class="form-section__title">Bus and route</p>

        <div class="form-row">
            <div class="field">
                <label class="field__label" for="bus_id">
                    Bus <span class="req" aria-hidden="true">*</span>
                </label>
                <select class="select" id="bus_id" name="bus_id"
                        data-label="Bus" data-rule-required required>
                    <option value="">-- Choose a bus --</option>
                    <?php foreach ($allBuses as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"
                                <?= (int) $schedule['bus_id'] === (int) $b['id'] ? 'selected' : '' ?>>
                            <?= e($b['bus_name']) ?> (<?= e($b['bus_type']) ?>, <?= e($b['capacity']) ?> seats)
                            <?= $b['status'] !== 'Active' ? ' - ' . e($b['status']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="field__error"></p>
            </div>

            <div class="field">
                <label class="field__label" for="route_id">
                    Route <span class="req" aria-hidden="true">*</span>
                </label>
                <select class="select" id="route_id" name="route_id"
                        data-label="Route" data-rule-required required>
                    <option value="">-- Choose a route --</option>
                    <?php foreach ($allRoutes as $r): ?>
                        <option value="<?= (int) $r['id'] ?>"
                                <?= (int) $schedule['route_id'] === (int) $r['id'] ? 'selected' : '' ?>>
                            <?= e($r['route_code']) ?> - <?= e($r['route_name']) ?>
                            <?= $r['status'] !== 'Active' ? ' (Inactive)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="field__error"></p>
            </div>
        </div>
    </div>

    <div class="form-section">
        <p class="form-section__title">Timing</p>

        <div class="form-row">
            <div class="field">
                <label class="field__label" for="departure_time">
                    Departure time <span class="req" aria-hidden="true">*</span>
                </label>
                <input class="input" type="time" id="departure_time" name="departure_time"
                       value="<?= e($schedule['departure_time']) ?>"
                       data-label="Departure time" data-rule-required data-rule-time required>
                <p class="field__error"></p>
            </div>

            <div class="field">
                <label class="field__label" for="arrival_time">
                    Arrival time <span class="req" aria-hidden="true">*</span>
                </label>
                <input class="input" type="time" id="arrival_time" name="arrival_time"
                       value="<?= e($schedule['arrival_time']) ?>"
                       data-label="Arrival time" data-rule-required data-rule-time required>
                <p class="field__hint">Must be later than the departure time.</p>
                <p class="field__error"></p>
            </div>

            <div class="field">
                <label class="field__label" for="status">
                    Status <span class="req" aria-hidden="true">*</span>
                </label>
                <select class="select" id="status" name="status" data-label="Status" required>
                    <?php foreach (['Active', 'Suspended'] as $opt): ?>
                        <option value="<?= e($opt) ?>" <?= $schedule['status'] === $opt ? 'selected' : '' ?>>
                            <?= e($opt) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="field__hint">Suspended trips are hidden from the public timetable.</p>
                <p class="field__error"></p>
            </div>
        </div>
    </div>

    <div class="form-section">
        <p class="form-section__title">Operating days</p>

        <div class="field">
            <div class="cluster mb-2" style="gap:8px;">
                <button class="btn btn--outline btn--sm" type="button" id="daysWorking">Working days (Sat-Thu)</button>
                <button class="btn btn--outline btn--sm" type="button" id="daysAll">Select all</button>
                <button class="btn btn--ghost btn--sm" type="button" id="daysNone">Clear</button>
            </div>

            <div class="check-grid">
                <?php foreach ($weekDays as $day): ?>
                    <label class="check-pill">
                        <input type="checkbox" name="operating_days[]" value="<?= e($day) ?>"
                               data-label="operating day" data-rule-group
                               <?= in_array($day, $selectedDays, true) ? 'checked' : '' ?>>
                        <span><?= e($day) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="field__error"></p>
        </div>

        <div class="field">
            <label class="field__label" for="notes">Note</label>
            <input class="input" type="text" id="notes" name="notes"
                   value="<?= e($schedule['notes']) ?>" maxlength="255"
                   placeholder="e.g. Morning shuttle towards the city"
                   data-label="Note" data-rule-max="255">
            <p class="field__error"></p>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn--primary" type="submit"><?= icon('check') ?> <?= e($submitLabel) ?></button>
        <a class="btn btn--outline" href="<?= e(url('admin/schedules/index.php')) ?>">Cancel</a>
    </div>
</form>
