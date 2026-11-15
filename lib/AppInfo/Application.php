<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
	public const APP_ID = 'motis';

	public function __construct() {
		require_once __DIR__ . '/../../vendor/autoload.php';
		parent::__construct(self::APP_ID);
	}
}
