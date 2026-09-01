<?php
/**
 * ---------------------------------------------------------------------------
 * SHARED HELPER FUNCTIONS
 * ---------------------------------------------------------------------------
 * Small reusable helpers used by both the public site and the admin panel:
 * output escaping, URLs, flash messages, CSRF protection, validation rules,
 * file uploads, formatting and the inline SVG icon set.
 * ---------------------------------------------------------------------------
 */

/* =========================================================================
   1. OUTPUT ESCAPING & URLs
   ========================================================================= */

/** Escape a value before printing it in HTML (protects against XSS). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build an absolute site URL from a project-relative path. */
function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

/**
 * URL of a CSS/JS/image file with a version stamp taken from the file's own
 * modification time. This stops the browser from showing an old cached
 * stylesheet after the file has been edited.
 */
function asset(string $path): string
{
    $relative = ltrim($path, '/');
    $file     = APP_ROOT . DIRECTORY_SEPARATOR . strtr($relative, chr(47), DIRECTORY_SEPARATOR);
    $version  = is_file($file) ? filemtime($file) : 1;

    return url($relative) . '?v=' . $version;
}

/** Redirect to a project-relative path and stop the script. */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Trim a submitted value (arrays are trimmed recursively). */
function clean($value)
{
    if (is_array($value)) {
        return array_map('clean', $value);
    }
    return trim((string) $value);
}

/** Read a POST value safely. */
function post(string $key, $default = '')
{
    return isset($_POST[$key]) ? clean($_POST[$key]) : $default;
}

/** Read a GET value safely. */
function get(string $key, $default = '')
{
    return isset($_GET[$key]) ? clean($_GET[$key]) : $default;
}

/** Name of the currently running script, e.g. "buses.php". */
function currentPage(): string
{
    return basename($_SERVER['SCRIPT_NAME']);
}

/** Returns "active" when $page matches the current script (for nav links). */
function navActive($page, string $class = 'active'): string
{
    $pages = (array) $page;
    return in_array(currentPage(), $pages, true) ? $class : '';
}

/* =========================================================================
   2. FLASH MESSAGES
   ========================================================================= */

/**
 * Queue a one-time message shown on the next page load.
 * $type: success | error | warning | info
 */
function setFlash(string $type, string $message): void
{
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Fetch and clear all queued flash messages. */
function takeFlashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/** Remember submitted form values so the form can be re-filled after an error. */
function keepOld(array $data): void
{
    unset($data['password'], $data['confirm_password'], $data['csrf_token']);
    $_SESSION['old'] = $data;
}

/** Read a remembered form value. */
function old(string $key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

/** Remember the list of server-side validation errors for the next request. */
function keepErrors(array $errors): void
{
    $_SESSION['form_errors'] = $errors;
}

/** Fetch and clear the server-side validation errors. */
function takeErrors(): array
{
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['form_errors']);
    return $errors;
}

/**
 * Print the server-side error summary above a form.
 * These are the errors PHP found - they appear even when JavaScript is off.
 */
function errorSummary(array $errors): string
{
    if (!$errors) {
        return '';
    }
    $html = '<div class="alert alert--error" role="alert">' . icon('alert')
          . '<div><strong>Please fix the following ' . count($errors)
          . (count($errors) === 1 ? ' problem' : ' problems') . ':</strong><ul>';
    foreach ($errors as $message) {
        $html .= '<li>' . e($message) . '</li>';
    }
    return $html . '</ul></div></div>';
}

/** Clear remembered form values (called once the form has been rendered). */
function clearOld(): void
{
    unset($_SESSION['old']);
}

/* =========================================================================
   3. CSRF PROTECTION
   ========================================================================= */

/** Get (or create) the CSRF token for this session. */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input that must be placed inside every POST form. */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/** Validate the token sent with a POST request. */
function csrfValid(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Abort the request when the CSRF token is missing or wrong. */
function requireCsrf(string $redirectTo): void
{
    if (!csrfValid()) {
        setFlash('error', 'Security check failed. Please submit the form again.');
        redirect($redirectTo);
    }
}

/* =========================================================================
   4. SERVER-SIDE VALIDATION RULES
   ========================================================================= */

/** True when a value is empty after trimming. */
function isBlank($value): bool
{
    return $value === null || trim((string) $value) === '';
}

function isValidEmail(string $value): bool
{
    return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
}

/** Bangladeshi mobile number, e.g. 01712345678 (optional +88 prefix). */
function isValidPhone(string $value): bool
{
    $digits = preg_replace('/[^0-9+]/', '', $value);
    return (bool) preg_match('/^(?:[+]?88)?01[3-9][0-9]{8}$/', $digits);
}

/** HH:MM 24-hour time, as produced by an <input type="time">. */
function isValidTime(string $value): bool
{
    return (bool) preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value);
}

/** YYYY-MM-DD date that really exists on the calendar. */
function isValidDate(string $value): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $value);
    return $d && $d->format('Y-m-d') === $value;
}

/** Whole number inside an inclusive range. */
function isIntBetween($value, int $min, int $max): bool
{
    return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]) !== false;
}

/** Decimal number inside an inclusive range. */
function isNumberBetween($value, float $min, float $max): bool
{
    if (!is_numeric($value)) {
        return false;
    }
    $n = (float) $value;
    return $n >= $min && $n <= $max;
}

/** Password policy: at least 6 characters, containing a letter and a digit. */
function isStrongPassword(string $value): bool
{
    return strlen($value) >= 6
        && preg_match('/[A-Za-z]/', $value) === 1
        && preg_match('/[0-9]/', $value) === 1;
}

/** Make sure a value is one of the allowed options (used for ENUM columns). */
function inList($value, array $allowed): bool
{
    return in_array($value, $allowed, true);
}

/* =========================================================================
   5. DATABASE HELPERS  (every query below is a prepared statement)
   ========================================================================= */

/** Run a prepared query and return the statement. */
function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/** Fetch all rows. */
function fetchAll(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

/** Fetch a single row (or null when nothing matched). */
function fetchOne(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

/** Fetch a single scalar value. */
function fetchValue(string $sql, array $params = [], $default = 0)
{
    $value = q($sql, $params)->fetchColumn();
    return $value === false ? $default : $value;
}

/** True when a value already exists in a column (used for unique checks). */
function existsInTable(string $table, string $column, $value, ?int $ignoreId = null): bool
{
    // Table and column names can never come from user input.
    $allowedTables  = ['users', 'buses', 'routes', 'announcements'];
    $allowedColumns = ['email', 'reg_number', 'route_code', 'title'];
    if (!in_array($table, $allowedTables, true) || !in_array($column, $allowedColumns, true)) {
        return false;
    }
    $sql    = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
    $params = [$value];
    if ($ignoreId !== null) {
        $sql     .= ' AND id <> ?';
        $params[] = $ignoreId;
    }
    return (int) fetchValue($sql, $params) > 0;
}

/** Record an admin action in the activity log. */
function logActivity(string $action, string $description): void
{
    try {
        q(
            'INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)',
            [$_SESSION['user_id'] ?? null, $action, $description]
        );
    } catch (Throwable $ex) {
        error_log('[LOG] ' . $ex->getMessage());
    }
}

/* =========================================================================
   6. FORMATTING HELPERS
   ========================================================================= */

/** 14:30  ->  2:30 PM */
function timeText(?string $time): string
{
    if (isBlank($time)) {
        return '--';
    }
    $ts = strtotime($time);
    return $ts ? date('g:i A', $ts) : (string) $time;
}

/** 95  ->  "1 h 35 min" */
function durationText($minutes): string
{
    $minutes = (int) $minutes;
    if ($minutes <= 0) {
        return '--';
    }
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h && $m) {
        return $h . ' h ' . $m . ' min';
    }
    return $h ? $h . ' h' : $m . ' min';
}

/** 2026-09-01  ->  01 Sep 2026 */
function dateText(?string $date): string
{
    if (isBlank($date)) {
        return '--';
    }
    $ts = strtotime($date);
    return $ts ? date('d M Y', $ts) : (string) $date;
}

/** Shorten long text for cards and tables. */
function excerpt(?string $text, int $limit = 120): string
{
    $text = trim(preg_replace('/[[:space:]]+/', ' ', strip_tags((string) $text)));
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    return mb_substr($text, 0, $limit) . '...';
}

/** "Saturday,Sunday" -> ["Sat", "Sun"] for compact day badges. */
function dayBadges(?string $csv): array
{
    $out = [];
    foreach (array_filter(array_map('trim', explode(',', (string) $csv))) as $day) {
        $out[] = mb_substr($day, 0, 3);
    }
    return $out;
}

/** CSS modifier used by the .badge component for a status value. */
function statusClass(string $status): string
{
    switch (strtolower($status)) {
        case 'active':
        case 'published':
            return 'badge--success';
        case 'maintenance':
        case 'draft':
        case 'suspended':
        case 'important':
            return 'badge--warning';
        case 'inactive':
            return 'badge--muted';
        case 'urgent':
            return 'badge--danger';
        default:
            return 'badge--info';
    }
}

/** Public URL of a bus photo, falling back to the SVG placeholder. */
function busImage(?string $file): string
{
    if (!isBlank($file) && is_file(UPLOAD_DIR . DIRECTORY_SEPARATOR . basename($file))) {
        return UPLOAD_URL . rawurlencode(basename($file));
    }
    return PLACEHOLDER_BUS;
}

/** Build a query string that keeps current filters but changes some keys. */
function withQuery(array $changes): string
{
    $params = array_merge($_GET, $changes);
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null) {
            unset($params[$key]);
        }
    }
    return $params ? '?' . http_build_query($params) : '';
}

/* =========================================================================
   7. FILE UPLOAD (bus photos)
   ========================================================================= */

/**
 * Validate and store an uploaded bus photo.
 *
 * @param  array       $file   one entry taken from $_FILES
 * @param  string|null $error  filled with a friendly message when it fails
 * @return string|null         stored filename, or null when nothing was saved
 */
function uploadBusImage(array $file, ?string &$error = null): ?string
{
    $error = null;

    // Nothing was chosen - allowed, the placeholder image will be used.
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'The image is larger than the server upload limit.',
            UPLOAD_ERR_FORM_SIZE  => 'The image is too large.',
            UPLOAD_ERR_PARTIAL    => 'The image was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server error: temporary folder is missing.',
            UPLOAD_ERR_CANT_WRITE => 'Server error: the image could not be written to disk.',
        ];
        $error = $messages[$file['error']] ?? 'The image could not be uploaded.';
        return null;
    }

    // The file must really have arrived through an HTTP upload.
    if (!is_uploaded_file($file['tmp_name'])) {
        $error = 'Invalid upload attempt.';
        return null;
    }

    if ($file['size'] > MAX_UPLOAD_BYTES) {
        $error = 'The image must be smaller than ' . round(MAX_UPLOAD_BYTES / 1048576, 1) . ' MB.';
        return null;
    }

    // Inspect the real content of the file, not the name the browser sent.
    if (@getimagesize($file['tmp_name']) === false) {
        $error = 'The uploaded file is not a valid image.';
        return null;
    }

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!isset($allowedMime[$mime])) {
        $error = 'Only JPG, PNG and WEBP images are allowed.';
        return null;
    }

    // The extension must agree with the detected MIME type.
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, explode(',', ALLOWED_IMAGE_EXT), true)) {
        $error = 'Only .jpg, .jpeg, .png and .webp files are allowed.';
        return null;
    }

    if (!is_dir(UPLOAD_DIR) && !@mkdir(UPLOAD_DIR, 0777, true)) {
        $error = 'The upload folder could not be created.';
        return null;
    }

    // Build a safe unique filename - the original name is never reused.
    $newName = 'bus_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowedMime[$mime];
    $target  = UPLOAD_DIR . DIRECTORY_SEPARATOR . $newName;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        $error = 'The image could not be saved on the server.';
        return null;
    }

    return $newName;
}

/** Remove a previously uploaded bus photo from disk. */
function deleteBusImage(?string $file): void
{
    if (isBlank($file)) {
        return;
    }
    $path = UPLOAD_DIR . DIRECTORY_SEPARATOR . basename($file);
    if (is_file($path)) {
        @unlink($path);
    }
}

/* =========================================================================
   8. INLINE SVG ICONS (keeps the project free of external icon libraries)
   ========================================================================= */

function icon(string $name, string $class = 'icon'): string
{
    static $paths = null;

    if ($paths === null) {
        $paths = [
            'bus'       => '<path d="M4 16V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10"/><path d="M4 11h16"/><path d="M3 16h18v2a1 1 0 0 1-1 1h-1v1a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1v-1H8v1a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-1H4a1 1 0 0 1-1-1z"/><circle cx="7.5" cy="14" r=".6"/><circle cx="16.5" cy="14" r=".6"/>',
            'route'     => '<circle cx="6" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="M8.5 6H15a3 3 0 0 1 0 6H9a3 3 0 0 0 0 6h6.5"/>',
            'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'megaphone' => '<path d="M3 11v2a1 1 0 0 0 1 1h3l6 4V6L7 10H4a1 1 0 0 0-1 1z"/><path d="M17 9a4 4 0 0 1 0 6"/><path d="M20 6.5a8 8 0 0 1 0 11"/>',
            'users'     => '<path d="M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1"/><circle cx="9.5" cy="8" r="3.5"/><path d="M21 19v-1a4 4 0 0 0-3-3.9"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8"/>',
            'grid'      => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
            'search'    => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
            'filter'    => '<path d="M4 5h16l-6.5 7.5V19l-3 2v-8.5z"/>',
            'check'     => '<path d="M20 6L9 17l-5-5"/>',
            'close'     => '<path d="M18 6L6 18M6 6l12 12"/>',
            'alert'     => '<path d="M12 3l9.5 17H2.5z"/><path d="M12 9.5v4"/><circle cx="12" cy="17" r=".7"/>',
            'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><circle cx="12" cy="8" r=".7"/>',
            'phone'     => '<path d="M5 3h3.5l1.5 4-2 1.5a12 12 0 0 0 5.5 5.5L15 12l4 1.5V17a2 2 0 0 1-2.2 2A15.5 15.5 0 0 1 3 5.2 2 2 0 0 1 5 3z"/>',
            'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3.5 7l8.5 6 8.5-6"/>',
            'pin'       => '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>',
            'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            'arrow'     => '<path d="M5 12h13"/><path d="M13 6l6 6-6 6"/>',
            'chevron'   => '<path d="M9 6l6 6-6 6"/>',
            'down'      => '<path d="M6 9l6 6 6-6"/>',
            'plus'      => '<path d="M12 5v14M5 12h14"/>',
            'edit'      => '<path d="M4 20h4L19 9a2.1 2.1 0 0 0-3-3L5 17z"/><path d="M15 6l3 3"/>',
            'trash'     => '<path d="M4 7h16"/><path d="M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7"/><path d="M6 7l1 12.2A1.8 1.8 0 0 0 8.8 21h6.4a1.8 1.8 0 0 0 1.8-1.8L18 7"/>',
            'eye'       => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>',
            'image'     => '<rect x="3" y="4" width="18" height="16" rx="2.5"/><circle cx="8.5" cy="9.5" r="1.7"/><path d="M4 17l5-4.5 3.5 3L16 12l4 4"/>',
            'shield'    => '<path d="M12 3l8 3v6c0 5-3.4 8.2-8 9.5C7.4 20.2 4 17 4 12V6z"/><path d="M9 12l2 2 4-4"/>',
            'star'      => '<path d="M12 4l2.4 5 5.6.8-4 3.9 1 5.5-5-2.7-5 2.7 1-5.5-4-3.9 5.6-.8z"/>',
            'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M3 10h18M8 3v4M16 3v4"/>',
            'logout'    => '<path d="M10 20H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4"/><path d="M15 8l4 4-4 4"/><path d="M19 12H9"/>',
            'login'     => '<path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/><path d="M9 8l-4 4 4 4"/><path d="M5 12h10"/>',
            'lock'      => '<rect x="4.5" y="10" width="15" height="10" rx="2.5"/><path d="M8 10V7.5a4 4 0 1 1 8 0V10"/>',
            'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M18.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-2.7 1.1v.3a2 2 0 1 1-4 0v-.2a1.6 1.6 0 0 0-2.8-1.1l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.6 1.6 0 0 0 3.5 14H3.2a2 2 0 1 1 0-4h.2a1.6 1.6 0 0 0 1.1-2.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.6 1.6 0 0 0 10 3.3V3a2 2 0 1 1 4 0v.2a1.6 1.6 0 0 0 2.8 1.1l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0 1.1 2.7h.3a2 2 0 1 1 0 4h-.2a1.6 1.6 0 0 0-1.4 1z"/>',
            'wifi'      => '<path d="M5 12.5a10 10 0 0 1 14 0"/><path d="M8.5 16a5 5 0 0 1 7 0"/><circle cx="12" cy="19" r=".8"/>',
            'gps'       => '<circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>',
            'seat'      => '<path d="M6 4h3a2 2 0 0 1 2 2v6H8a2 2 0 0 1-2-2z"/><path d="M6 14h9a3 3 0 0 1 3 3v3"/><path d="M4 20h2"/>',
            'wrench'    => '<path d="M15 7a4 4 0 0 1 5 5l-8 8-3-3 8-8a4 4 0 0 1-2-2z"/><circle cx="7" cy="17" r="2.5"/>',
            'file'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>',
            'inbox'     => '<path d="M4 13h4l1.5 3h5L16 13h4"/><path d="M4 13l2.5-8h11L20 13v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z"/>',
            'activity'  => '<path d="M3 12h4l3 8 4-16 3 8h4"/>',
            'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
            'road'      => '<path d="M7 3L4 21M17 3l3 18M12 4v3M12 11v3M12 18v3"/>',
        ];
    }

    $path = $paths[$name] ?? $paths['info'];

    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" '
        . 'focusable="false">' . $path . '</svg>';
}
