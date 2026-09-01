<?php
/**
 * ---------------------------------------------------------------------------
 * CENTRAL CONFIGURATION
 * ---------------------------------------------------------------------------
 * Everything that may need to change when the project is moved to another
 * computer lives in this single file. Nothing else in the project hard-codes
 * the university name, database credentials or the site URL.
 * ---------------------------------------------------------------------------
 */

/* ------------------------- 1. Site identity ------------------------------ */
define('UNIVERSITY_NAME', 'Khulna University of Engineering & Technology');
define('UNIVERSITY_SHORT', 'KUET');
define('SITE_NAME',        'KUET Bus Service');
define('SITE_TAGLINE',     'Safe. Reliable. Connected.');
define('SITE_DESCRIPTION', 'Official transport portal of ' . UNIVERSITY_NAME . ' — buses, routes, stoppages and daily schedules in one place.');

/* Transport office contact details (shown on the Contact page & footer) */
define('OFFICE_ADDRESS', 'Transport Office, Administrative Building, KUET, Fulbarigate, Khulna-9203');
define('OFFICE_PHONE',   '+880 41-769468');
define('OFFICE_EMAIL',   'transport@kuet.ac.bd');
define('OFFICE_HOURS',   'Saturday – Thursday, 9:00 AM – 5:00 PM');

/* ------------------------- 2. Database ----------------------------------- */
/* IMPORTANT: this XAMPP installation runs MySQL/MariaDB on port 4306 (NOT 3306) */
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '4306');
define('DB_NAME', 'university_bus_service');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/* ------------------------- 3. Paths & URLs -------------------------------- */
/* Absolute filesystem path to the project root (no trailing slash) */
define('APP_ROOT', dirname(__DIR__));

/**
 * BASE_URL is detected automatically from the folder name, so the project
 * works whether it lives in htdocs/test/ or htdocs/university-bus-service/.
 * If auto-detection ever fails, simply replace the block below with e.g.
 *     define('BASE_URL', '/university-bus-service/');
 */
if (!defined('BASE_URL')) {
    $docRoot  = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $appRoot  = realpath(APP_ROOT);
    $detected = '/';
    if ($docRoot && $appRoot) {
        $docRoot = strtr($docRoot, DIRECTORY_SEPARATOR, chr(47));
        $appRoot = strtr($appRoot, DIRECTORY_SEPARATOR, chr(47));
        if (strpos($appRoot, $docRoot) === 0) {
            $detected = substr($appRoot, strlen($docRoot));
            $detected = '/' . trim($detected, '/');
        }
    }
    define('BASE_URL', rtrim($detected, '/') . '/');
}

/* Where uploaded bus photos are stored (folder + public URL) */
define('UPLOAD_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'buses');
define('UPLOAD_URL', BASE_URL . 'assets/uploads/buses/');
define('PLACEHOLDER_BUS', BASE_URL . 'assets/images/placeholders/bus-placeholder.svg');

/* ------------------------- 4. Application rules --------------------------- */
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024);                 // 2 MB per bus photo
define('ALLOWED_IMAGE_EXT', 'jpg,jpeg,png,webp');            // allowed photo types
define('ITEMS_PER_PAGE', 9);                                 // public listing page size
define('OPERATING_DAYS', 'Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday');

/* ------------------------- 5. Error reporting ----------------------------- */
/* Set to false before showing the project publicly; true while developing. */
define('APP_DEBUG', false);

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . DIRECTORY_SEPARATOR . 'error_log.txt');

date_default_timezone_set('Asia/Dhaka');
