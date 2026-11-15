<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Controller;

use OCA\Motis\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;

class SettingsController extends Controller {

	public function __construct(
		IRequest $request,
		private IConfig $config,
		private IGroupManager $groupManager,
		private IUserSession $userSession,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	public function update(): JSONResponse {
		$user = $this->userSession->getUser();

		if ($user instanceof IUser && $this->groupManager->isAdmin($user->getUID())) {
			$baseUrl = trim((string)$this->request->getParam('baseUrl', ''));
			$apiKey = trim((string)$this->request->getParam('apiKey', ''));
			$timeout = trim((string)$this->request->getParam('timeout', ''));
			$allowedGroups = $this->request->getParam('allowedGroups', []);

			if ($baseUrl !== '' && !$this->isValidBaseUrl($baseUrl)) {
				return new JSONResponse(['error' => 'Invalid MOTIS server URL.'], 422);
			}

			if ($timeout !== '' && !preg_match('/^[1-9]\d{0,2}$/', $timeout)) {
				return new JSONResponse(['error' => 'Invalid timeout value.'], 422);
			}

			if (!is_array($allowedGroups)) {
				$allowedGroups = [];
			}
			$allowedGroups = array_values(array_unique(array_filter($allowedGroups, static fn ($gid): bool => is_string($gid) && $gid !== '')));

			$this->config->setAppValue(Application::APP_ID, 'base_url', $baseUrl);
			$this->config->setAppValue(Application::APP_ID, 'api_key', $apiKey);
			$this->config->setAppValue(Application::APP_ID, 'timeout', $timeout);
			$this->config->setAppValue(Application::APP_ID, 'allowed_groups', json_encode($allowedGroups, JSON_THROW_ON_ERROR));

			return new JSONResponse(['status' => 'success']);
		}

		return new JSONResponse(['error' => 'You are not allowed to change the Motis settings.'], 403);
	}

	private function isValidBaseUrl(string $url): bool {
		$parsed = parse_url($url);
		return isset($parsed['scheme']) && ($parsed['scheme'] === 'http' || $parsed['scheme'] === 'https') && isset($parsed['host']);
	}
}
