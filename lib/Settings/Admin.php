<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Settings;

use OCA\Motis\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\L10N\IFactory;
use OCP\Settings\ISettings;

class Admin implements ISettings {

	public function __construct(
		private IConfig $config,
		private IGroupManager $groupManager,
		private IFactory $l10nFactory,
	) {
	}

	public function getForm(): TemplateResponse {
		$l = $this->l10nFactory->get(Application::APP_ID);

		$allowedGroups = json_decode($this->config->getAppValue('motis', 'allowed_groups') ?: '[]', true);
		if (!is_array($allowedGroups)) {
			$allowedGroups = [];
		}

		$groupOptions = [];
		/** @var IGroup $group */
		foreach ($this->groupManager->search('', 500) as $group) {
			$displayName = $group->getDisplayName();
			$groupOptions[] = [
				'id' => $group->getGID(),
				'displayName' => $displayName !== '' ? $displayName : $group->getGID(),
			];
		}

		return new TemplateResponse(Application::APP_ID, 'admin-settings', [
			'baseUrl' => $this->config->getAppValue('motis', 'base_url'),
			'apiKey' => $this->config->getAppValue('motis', 'api_key'),
			'timeout' => $this->config->getAppValue('motis', 'timeout'),
			'allowedGroups' => array_values(array_filter($allowedGroups, 'is_string')),
			'groups' => $groupOptions,
			'tr' => [
				'pageTitle' => $l->t('Motis settings'),
				'intro' => $l->t('Configure the MOTIS server connection and control which groups may use the app.'),
				'baseUrlLabel' => $l->t('MOTIS server URL'),
				'baseUrlHint' => $l->t('Leave empty to use the Transitous community server (https://api.transitous.org).'),
				'apiKeyLabel' => $l->t('API key'),
				'apiKeyHint' => $l->t('Optional authentication token for self-hosted MOTIS servers. It is stored now and used automatically once the PHP client supports authenticated requests.'),
				'timeoutLabel' => $l->t('Request timeout (seconds)'),
				'timeoutHint' => $l->t('Optional: overrides the default request timeout (1-999 seconds).'),
				'allowedGroupsLabel' => $l->t('Allowed groups'),
				'allowedGroupsHint' => $l->t('Select the groups that may use the app. Without a selection, all users have access.'),
				'saveLabel' => $l->t('Save'),
				'savedLabel' => $l->t('Saved'),
				'saveErrorLabel' => $l->t('Could not save settings'),
			],
		]);
	}

	public function getSection(): string {
		return 'additional';
	}

	public function getPriority(): int {
		return 10;
	}
}
