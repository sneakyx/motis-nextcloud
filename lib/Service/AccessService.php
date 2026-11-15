<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Service;

use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;

class AccessService {

	public function __construct(
		private IConfig $config,
		private IGroupManager $groupManager,
	) {
	}

	/**
	 * @return list<string>
	 */
	public function getAllowedGroups(): array {
		$raw = $this->config->getAppValue('motis', 'allowed_groups');

		if ($raw === '') {
			return [];
		}

		try {
			$decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException) {
			return [];
		}

		if (!is_array($decoded)) {
			return [];
		}

		return array_values(array_unique(array_filter($decoded, static fn ($gid): bool => is_string($gid) && $gid !== '')));
	}

	public function isAccessAllowed(?IUser $user): bool {
		if ($user === null) {
			return false;
		}

		$allowedGroups = $this->getAllowedGroups();

		if ($allowedGroups === []) {
			return true;
		}

		$userGroups = $this->groupManager->getUserGroupIds($user);

		return array_intersect($allowedGroups, $userGroups) !== [];
	}
}
