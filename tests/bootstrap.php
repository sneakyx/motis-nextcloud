<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * PHPUnit bootstrap for the unit test suite.
 *
 * The production autoloader (vendor/autoload.php) is generated without dev
 * dependencies ("composer dump-autoload --no-dev") so that the Nextcloud
 * runtime, which includes it through the Application class, never resolves
 * core namespaces (OCP\, OC\) to the stub packages. This keeps a dev instance
 * that is bind-mounted from the repository working while composer dev
 * dependencies are installed.
 *
 * The test run needs those mappings, so they are registered here for the
 * test process only.
 */
require_once __DIR__ . '/../vendor/autoload.php';

$devPrefixes = [
	'OCA\Motis\Test\\' => __DIR__ . '/',
	'OCA\Motis\\' => __DIR__ . '/../lib/',
	'OCP\\' => __DIR__ . '/../vendor/nextcloud/ocp/OCP/',
	'OC\\' => __DIR__ . '/../vendor/nextcloud/ocp/OC/',
];

spl_autoload_register(static function (string $class) use ($devPrefixes): void {
	$prefix = $class;
	while (($pos = strrpos($prefix, '\\')) !== false) {
		$prefix = substr($prefix, 0, $pos + 1);
		if (isset($devPrefixes[$prefix])) {
			$file = $devPrefixes[$prefix]
				. str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
			if (is_file($file)) {
				require_once $file;
				return;
			}
		}
	}
});
