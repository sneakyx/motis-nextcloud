<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Test\Unit\Settings;

use OCA\Motis\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\L10N\IFactory;
use PHPUnit\Framework\TestCase;

class AdminTest extends TestCase {

	private IConfig $config;
	private IGroupManager $groupManager;
	private IFactory $l10nFactory;

	private Admin $settings;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->l10nFactory = $this->createMock(IFactory::class);

		$l = $this->createMock(IL10N::class);
		$l->method('t')->willReturnCallback(static fn (string $text) => $text);
		$this->l10nFactory->method('get')->willReturn($l);

		$this->settings = new Admin($this->config, $this->groupManager, $this->l10nFactory);
	}

	public function testGetSection(): void {
		$this->assertSame('additional', $this->settings->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(10, $this->settings->getPriority());
	}

	public function testGetFormRendersAdminSettingsTemplate(): void {
		$this->config->method('getAppValue')->willReturnCallback(
			static function (string $app, string $key, string $default = '') {
				return match ($key) {
					'base_url' => 'https://motis.example.com',
					'api_key' => 'secret',
					'timeout' => '42',
					'allowed_groups' => '["staff"]',
					default => $default,
				};
			},
		);

		$adminGroup = $this->createMock(IGroup::class);
		$adminGroup->method('getGID')->willReturn('admin');
		$adminGroup->method('getDisplayName')->willReturn('Administrators');

		$staffGroup = $this->createMock(IGroup::class);
		$staffGroup->method('getGID')->willReturn('staff');
		$staffGroup->method('getDisplayName')->willReturn('');

		$this->groupManager->method('search')->willReturn([$adminGroup, $staffGroup]);

		$response = $this->settings->getForm();

		$this->assertInstanceOf(TemplateResponse::class, $response);
		$this->assertSame('admin-settings', $response->getTemplateName());

		$params = $response->getParams();
		$this->assertSame('https://motis.example.com', $params['baseUrl']);
		$this->assertSame('secret', $params['apiKey']);
		$this->assertSame('42', $params['timeout']);
		$this->assertSame(['staff'], $params['allowedGroups']);
		$this->assertSame([
			['id' => 'admin', 'displayName' => 'Administrators'],
			['id' => 'staff', 'displayName' => 'staff'],
		], $params['groups']);
		$this->assertSame('Motis settings', $params['tr']['pageTitle']);
	}
}
