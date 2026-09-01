<?php
/**
 * Shared SERVER-SIDE validation for the route form (create.php + edit.php).
 */
function validateRouteInput(array $in, ?int $ignoreId = null): array
{
    $errors = [];

    /* Route name -------------------------------------------------------- */
    if (isBlank($in['route_name'])) {
        $errors[] = 'The route name is required.';
    } elseif (mb_strlen($in['route_name']) < 3 || mb_strlen($in['route_name']) > 120) {
        $errors[] = 'The route name must be between 3 and 120 characters.';
    }

    /* Route code (unique) ----------------------------------------------- */
    if (isBlank($in['route_code'])) {
        $errors[] = 'The route code is required.';
    } elseif (!preg_match('/^[A-Za-z0-9-]{2,20}$/', $in['route_code'])) {
        $errors[] = 'The route code may only contain letters, numbers and dashes (2-20 characters).';
    } elseif (existsInTable('routes', 'route_code', $in['route_code'], $ignoreId)) {
        $errors[] = 'Another route already uses this route code.';
    }

    /* Start & destination ------------------------------------------------ */
    if (isBlank($in['start_point'])) {
        $errors[] = 'The starting point is required.';
    } elseif (mb_strlen($in['start_point']) > 120) {
        $errors[] = 'The starting point must not be longer than 120 characters.';
    }

    if (isBlank($in['destination'])) {
        $errors[] = 'The destination is required.';
    } elseif (mb_strlen($in['destination']) > 120) {
        $errors[] = 'The destination must not be longer than 120 characters.';
    }

    if (!isBlank($in['start_point']) && !isBlank($in['destination'])
        && mb_strtolower($in['start_point']) === mb_strtolower($in['destination'])) {
        $errors[] = 'The starting point and the destination cannot be the same place.';
    }

    /* Distance & duration ------------------------------------------------ */
    if (isBlank($in['distance_km'])) {
        $errors[] = 'The distance is required.';
    } elseif (!isNumberBetween($in['distance_km'], 0.1, 500)) {
        $errors[] = 'The distance must be a number between 0.1 and 500 kilometres.';
    }

    if (isBlank($in['duration_min'])) {
        $errors[] = 'The estimated duration is required.';
    } elseif (!isIntBetween($in['duration_min'], 1, 600)) {
        $errors[] = 'The duration must be a whole number of minutes between 1 and 600.';
    }

    /* Status & description ----------------------------------------------- */
    if (!inList($in['status'], ['Active', 'Inactive'])) {
        $errors[] = 'Please choose a valid status.';
    }

    if (!isBlank($in['description']) && mb_strlen($in['description']) > 2000) {
        $errors[] = 'The description must not be longer than 2000 characters.';
    }

    return $errors;
}

/**
 * SERVER-SIDE validation for a single route stop.
 */
function validateStopInput(array $in): array
{
    $errors = [];

    if (isBlank($in['stop_name'])) {
        $errors[] = 'The stop name is required.';
    } elseif (mb_strlen($in['stop_name']) < 2 || mb_strlen($in['stop_name']) > 120) {
        $errors[] = 'The stop name must be between 2 and 120 characters.';
    }

    if (!isBlank($in['stop_description']) && mb_strlen($in['stop_description']) > 255) {
        $errors[] = 'The stop description must not be longer than 255 characters.';
    }

    if (!isBlank($in['arrival_time']) && !isValidTime($in['arrival_time'])) {
        $errors[] = 'Please enter a valid arrival time (HH:MM).';
    }

    return $errors;
}
