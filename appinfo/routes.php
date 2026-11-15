<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\AppInfo;

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'settings#update', 'url' => '/admin-settings', 'verb' => 'POST'],
		['name' => 'motis#geocode', 'url' => '/geocode', 'verb' => 'GET'],
		['name' => 'motis#plan', 'url' => '/plan', 'verb' => 'GET'],
	['name' => 'motis#events', 'url' => '/events', 'verb' => 'GET'],
	],
];
