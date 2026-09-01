<?php
/**
 * ---------------------------------------------------------------------------
 * AUTHENTICATION & AUTHORISATION
 * ---------------------------------------------------------------------------
 * Session handling plus the guard functions that protect private pages.
 * Authorisation is always enforced on the SERVER - hiding a button in the
 * navigation is never treated as a security measure.
 * ---------------------------------------------------------------------------
 */

/** Start the PHP session once per request (used only to remember the login). */
function startSessionOnce(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/** True when somebody is logged in. */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/** True when the logged-in account is the administrator. */
function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? 'user') === 'admin';
}

/** The logged-in user's row (fetched once per request), or null. */
function currentUser(): ?array
{
    static $user = null;
    static $loaded = false;

    if ($loaded) {
        return $user;
    }
    $loaded = true;

    if (!isLoggedIn()) {
        return null;
    }

    $user = fetchOne(
        'SELECT id, full_name, email, student_id, department, phone, role FROM users WHERE id = ?',
        [(int) $_SESSION['user_id']]
    );

    // The account was deleted while the session was still alive.
    if ($user === null) {
        logoutUser();
    }

    return $user;
}

/** Store the login in the session (called after a successful password check). */
function loginUser(array $user): void
{
    session_regenerate_id(true);              // prevents session fixation
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
}

/** Destroy the session completely. */
function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/** Block the page for visitors who are not logged in. */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? url('index.php');
        setFlash('warning', 'Please log in to continue.');
        redirect('login.php');
    }
}

/**
 * Block the page for everybody except the administrator.
 * A logged-in normal user gets the 403 page - not a redirect - so it is
 * obvious that the restriction is enforced by the server.
 */
function requireAdmin(): void
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? url('admin/index.php');
        setFlash('warning', 'Please log in with the administrator account.');
        redirect('login.php');
    }

    if (!isAdmin()) {
        require APP_ROOT . DIRECTORY_SEPARATOR . '403.php';
        exit;
    }
}

/** Send visitors who are already logged in away from login/register pages. */
function redirectIfLoggedIn(): void
{
    if (isLoggedIn()) {
        redirect(isAdmin() ? 'admin/index.php' : 'index.php');
    }
}
