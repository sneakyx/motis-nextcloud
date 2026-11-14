<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Service;

use OCP\IConfig;
use Sneakyx\MotisPhpClient\MotisClient;

class MotisClientFactory {

	public const DEFAULT_BASE_URL = 'https://api.transitous.org';

	public function __construct(
		private IConfig $config,
	) {
	}

	public function createClient(): MotisClient {
		$baseUrl = $this->config->getAppValue('motis', 'base_url');

		if ($baseUrl === '') {
			$baseUrl = self::DEFAULT_BASE_URL;
		}

		return new MotisClient($baseUrl);
	}
}
