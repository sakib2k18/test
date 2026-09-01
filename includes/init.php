<?php
/**
 * ---------------------------------------------------------------------------
 * BOOTSTRAP
 * ---------------------------------------------------------------------------
 * Every page in the project starts with:  require_once '.../includes/init.php';
 * It loads the configuration, the database connection, the helper functions
 * and the authentication layer, then starts the session.
 * ---------------------------------------------------------------------------
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

startSessionOnce();
