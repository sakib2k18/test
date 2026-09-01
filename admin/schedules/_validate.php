<?php
/**
 * Shared SERVER-SIDE validation for the schedule form.
 */
function validateScheduleInput(array $in): array
{
    $errors    = [];
    $validDays = explode(',', OPERATING_DAYS);

    /* The bus and the route must really exist (foreign keys). ------------- */
    if ((int) $in['bus_id'] <= 0) {
        $errors[] = 'Please choose a bus.';
    } elseif (!fetchOne('SELECT id FROM buses WHERE id = ?', [(int) $in['bus_id']])) {
        $errors[] = 'The selected bus does not exist.';
    }

    if ((int) $in['route_id'] <= 0) {
        $errors[] = 'Please choose a route.';
    } elseif (!fetchOne('SELECT id FROM routes WHERE id = ?', [(int) $in['route_id']])) {
        $errors[] = 'The selected route does not exist.';
    }

    /* Times --------------------------------------------------------------- */
    if (isBlank($in['departure_time'])) {
        $errors[] = 'The departure time is required.';
    } elseif (!isValidTime($in['departure_time'])) {
        $errors[] = 'Please enter a valid departure time (HH:MM).';
    }

    if (isBlank($in['arrival_time'])) {
        $errors[] = 'The arrival time is required.';
    } elseif (!isValidTime($in['arrival_time'])) {
        $errors[] = 'Please enter a valid arrival time (HH:MM).';
    }

    if (isValidTime((string) $in['departure_time']) && isValidTime((string) $in['arrival_time'])
        && strtotime($in['arrival_time']) <= strtotime($in['departure_time'])) {
        $errors[] = 'The arrival time must be later than the departure time.';
    }

    /* Operating days ------------------------------------------------------ */
    $days = array_values(array_intersect((array) $in['operating_days'], $validDays));
    if (!$days) {
        $errors[] = 'Please select at least one operating day.';
    }

    /* Status & notes ------------------------------------------------------ */
    if (!inList($in['status'], ['Active', 'Suspended'])) {
        $errors[] = 'Please choose a valid status.';
    }

    if (!isBlank($in['notes']) && mb_strlen($in['notes']) > 255) {
        $errors[] = 'The note must not be longer than 255 characters.';
    }

    return $errors;
}

/**
 * Keep only the real weekday names, in the correct week order.
 */
function normaliseDays(array $submitted): string
{
    $order = explode(',', OPERATING_DAYS);
    $kept  = array_values(array_intersect($order, $submitted));
    return implode(',', $kept);
}

/**
 * Warn when the same bus is already booked on an overlapping trip on a day
 * it also runs here. This is a business rule, not a database constraint.
 *
 * @return string|null  a warning message, or null when there is no clash
 */
function findScheduleClash(int $busId, string $departure, string $arrival, array $days, ?int $ignoreId = null): ?string
{
    $sql    = 'SELECT s.id, s.departure_time, s.arrival_time, s.operating_days, r.route_code
                 FROM schedules s
                 JOIN routes r ON r.id = s.route_id
                WHERE s.bus_id = ? AND s.status = ?';
    $params = [$busId, 'Active'];

    if ($ignoreId !== null) {
        $sql     .= ' AND s.id <> ?';
        $params[] = $ignoreId;
    }

    foreach (fetchAll($sql, $params) as $row) {
        $sameDays = array_intersect($days, array_map('trim', explode(',', $row['operating_days'])));
        if (!$sameDays) {
            continue;
        }
        // Two time ranges overlap when each one starts before the other ends.
        if (strtotime($departure) < strtotime($row['arrival_time'])
            && strtotime($row['departure_time']) < strtotime($arrival)) {
            return 'This bus already runs on route ' . $row['route_code'] . ' between '
                 . timeText($row['departure_time']) . ' and ' . timeText($row['arrival_time'])
                 . ' on ' . implode(', ', $sameDays) . '.';
        }
    }

    return null;
}
