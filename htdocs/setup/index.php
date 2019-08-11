<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

// XXX: try reading $_ENV['HOSTNAME'] and then ask the user if nothing could be found
// XXX: dynamically check the email blob and skips the email if it is bigger than 16MB on PHP4 versions

use Eventum\Kernel;

ini_set('memory_limit', '64M');
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_STRICT);
set_time_limit(0);

require_once __DIR__ . '/../../globals.php';

header('Content-Type: text/html; charset=UTF-8');

$configFile = dirname(__DIR__) . '/../config/config.php';
$have_config = file_exists($configFile) && filesize($configFile);
// get out if already configured
if ($have_config) {
    header('Location: ../');
    exit(0);
}

require_once APP_PATH . '/autoload.php';

// set default timezone to utc to avoid default timezone not set warnings
date_default_timezone_set(@date_default_timezone_get());

if (!file_exists($privateKeyFile = Setup::getConfigPath() . '/private_key.php') || !filesize($privateKeyFile)) {
    Auth::generatePrivateKey();
}

Kernel::handleRequest();
