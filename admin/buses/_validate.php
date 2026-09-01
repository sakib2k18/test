<?php
/**
 * Shared SERVER-SIDE validation for the bus form.
 * Used by both create.php and edit.php so the rules can never drift apart.
 *
 * @param  array    $in        the cleaned POST values
 * @param  int|null $ignoreId  the bus being edited (skipped in the unique check)
 * @return array               list of human readable error messages
 */
function validateBusInput(array $in, ?int $ignoreId = null): array
{
    $errors = [];

    $typeOptions   = ['Regular', 'Student Bus', 'Faculty Bus (AC)', 'Faculty Bus (Non AC)'];
    $statusOptions = ['Active', 'Maintenance', 'Inactive'];

    /* Bus name --------------------------------------------------------- */
    if (isBlank($in['bus_name'])) {
        $errors[] = 'The bus name / number is required.';
    } elseif (mb_strlen($in['bus_name']) < 2 || mb_strlen($in['bus_name']) > 80) {
        $errors[] = 'The bus name must be between 2 and 80 characters.';
    }

    /* Registration number (must be unique) ------------------------------ */
    if (isBlank($in['reg_number'])) {
        $errors[] = 'The registration number is required.';
    } elseif (mb_strlen($in['reg_number']) < 3 || mb_strlen($in['reg_number']) > 40) {
        $errors[] = 'The registration number must be between 3 and 40 characters.';
    } elseif (existsInTable('buses', 'reg_number', $in['reg_number'], $ignoreId)) {
        $errors[] = 'Another bus is already registered with this registration number.';
    }

    /* Model ------------------------------------------------------------- */
    if (!isBlank($in['model']) && mb_strlen($in['model']) > 80) {
        $errors[] = 'The model name must not be longer than 80 characters.';
    }

    /* Capacity ---------------------------------------------------------- */
    if (isBlank($in['capacity'])) {
        $errors[] = 'The seating capacity is required.';
    } elseif (!isIntBetween($in['capacity'], 1, 120)) {
        $errors[] = 'The seating capacity must be a whole number between 1 and 120.';
    }

    /* Enumerations ------------------------------------------------------ */
    if (!inList($in['bus_type'], $typeOptions)) {
        $errors[] = 'Please choose a valid bus type.';
    }
    if (!inList($in['status'], $statusOptions)) {
        $errors[] = 'Please choose a valid status.';
    }

    /* Driver ------------------------------------------------------------ */
    if (!isBlank($in['driver_name']) && mb_strlen($in['driver_name']) > 80) {
        $errors[] = 'The driver name must not be longer than 80 characters.';
    }
    if (!isBlank($in['driver_contact']) && !isValidPhone($in['driver_contact'])) {
        $errors[] = 'Please enter a valid driver mobile number, for example 01712345678.';
    }

    /* Description ------------------------------------------------------- */
    if (!isBlank($in['description']) && mb_strlen($in['description']) > 2000) {
        $errors[] = 'The description must not be longer than 2000 characters.';
    }

    return $errors;
}

/**
 * Replace the facility links of a bus with the submitted selection.
 * Only ids that really exist in the facilities table are stored.
 */
function saveBusFacilities(int $busId, array $facilityIds): void
{
    q('DELETE FROM bus_facilities WHERE bus_id = ?', [$busId]);

    $valid = array_column(fetchAll('SELECT id FROM facilities'), 'id');

    foreach (array_unique($facilityIds) as $facilityId) {
        $facilityId = (int) $facilityId;
        if (in_array($facilityId, array_map('intval', $valid), true)) {
            q('INSERT INTO bus_facilities (bus_id, facility_id) VALUES (?, ?)', [$busId, $facilityId]);
        }
    }
}
