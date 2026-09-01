<?php
/**
 * Shared SERVER-SIDE validation for the announcement form.
 */
function validateAnnouncementInput(array $in, ?int $ignoreId = null): array
{
    $errors = [];

    if (isBlank($in['title'])) {
        $errors[] = 'The title is required.';
    } elseif (mb_strlen($in['title']) < 5 || mb_strlen($in['title']) > 160) {
        $errors[] = 'The title must be between 5 and 160 characters.';
    } elseif (existsInTable('announcements', 'title', $in['title'], $ignoreId)) {
        $errors[] = 'Another announcement already uses this exact title.';
    }

    if (isBlank($in['short_description'])) {
        $errors[] = 'The short description is required.';
    } elseif (mb_strlen($in['short_description']) < 10 || mb_strlen($in['short_description']) > 255) {
        $errors[] = 'The short description must be between 10 and 255 characters.';
    }

    if (isBlank($in['content'])) {
        $errors[] = 'The full content is required.';
    } elseif (mb_strlen($in['content']) < 20) {
        $errors[] = 'The full content must be at least 20 characters long.';
    } elseif (mb_strlen($in['content']) > 8000) {
        $errors[] = 'The full content must not be longer than 8000 characters.';
    }

    if (!inList($in['priority'], ['Normal', 'Important', 'Urgent'])) {
        $errors[] = 'Please choose a valid priority.';
    }

    if (!inList($in['status'], ['Published', 'Draft'])) {
        $errors[] = 'Please choose a valid status.';
    }

    if (isBlank($in['published_on'])) {
        $errors[] = 'The publication date is required.';
    } elseif (!isValidDate($in['published_on'])) {
        $errors[] = 'Please enter a valid publication date.';
    }

    return $errors;
}
