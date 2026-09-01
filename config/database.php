<?php
/**
 * ---------------------------------------------------------------------------
 * DATABASE CONNECTION (PDO)
 * ---------------------------------------------------------------------------
 * A single shared PDO connection is created here and reused by every page.
 * Prepared statements are used everywhere in this project, so user input is
 * never concatenated into SQL.
 *
 * NOTE: MySQL/MariaDB runs on port 4306 in this XAMPP setup (see config.php).
 * ---------------------------------------------------------------------------
 */

require_once __DIR__ . '/config.php';

/**
 * Returns the shared PDO connection (creates it on first call).
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw on SQL errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                  // real prepared statements
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Never show raw database errors to the visitor - log them instead.
        error_log('[DB] ' . $e->getMessage());
        http_response_code(500);
        $detail = APP_DEBUG ? htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') : '';
        die(
            '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Database unavailable</title>'
            . '<style>body{font-family:system-ui,Segoe UI,Arial,sans-serif;background:#f4f6fb;color:#102a53;'
            . 'display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;padding:24px}'
            . '.box{background:#fff;max-width:560px;padding:40px;border-radius:18px;'
            . 'box-shadow:0 20px 45px rgba(16,42,83,.12)}h1{margin:0 0 12px;font-size:22px}'
            . 'code{background:#eef2f9;padding:2px 6px;border-radius:6px;font-size:13px}'
            . 'li{margin-bottom:6px;line-height:1.6}</style></head><body><div class="box">'
            . '<h1>Cannot connect to the database</h1>'
            . '<p>The site could not reach the MySQL server. Please check the following:</p><ul>'
            . '<li>XAMPP &rarr; MySQL module is <strong>running</strong>.</li>'
            . '<li>MySQL is listening on port <code>' . DB_PORT . '</code>.</li>'
            . '<li>The database <code>' . DB_NAME . '</code> has been imported from '
            . '<code>database/university_bus_service.sql</code>.</li>'
            . '</ul>' . ($detail ? '<p><small>' . $detail . '</small></p>' : '')
            . '</div></body></html>'
        );
    }

    return $pdo;
}
