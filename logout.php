<?php
/**
 * LOG OUT
 * Destroys the session and returns the visitor to the homepage.
 */
require_once __DIR__ . '/includes/init.php';

if (isLoggedIn()) {
    $name = $_SESSION['user_name'] ?? 'A user';
    logActivity('Logout', $name . ' logged out.');
    logoutUser();

    // A brand new session is needed so the goodbye message can be shown.
    startSessionOnce();
    setFlash('success', 'You have been logged out successfully.');
}

redirect('index.php');
