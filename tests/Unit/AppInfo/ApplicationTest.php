<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Test\Unit\AppInfo;

use OCA\Motis\AppInfo\Application;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase {

	public function testAppId(): void {
		$this->assertSame('motis', Application::APP_ID);
	}
}
