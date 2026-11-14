<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Test\Unit\Service;

use OCA\Motis\Service\MotisClientFactory;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;
use Sneakyx\MotisPhpClient\MotisClient;

class MotisClientFactoryTest extends TestCase {

	private IConfig $config;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
	}

	public function testCreateClientUsesConfiguredBaseUrl(): void {
		$this->config->method('getAppValue')
			->with('motis', 'base_url')
			->willReturn('https://motis.example.org');

		$factory = new MotisClientFactory($this->config);

		$this->assertInstanceOf(MotisClient::class, $factory->createClient());
	}

	public function testCreateClientFallsBackToDefaultBaseUrl(): void {
		$this->config->method('getAppValue')
			->with('motis', 'base_url')
			->willReturn('');

		$factory = new MotisClientFactory($this->config);

		$this->assertInstanceOf(MotisClient::class, $factory->createClient());
	}
}
