<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Test\Unit\Service;

use OCA\Motis\Service\AccessService;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use PHPUnit\Framework\TestCase;

class AccessServiceTest extends TestCase {

	private IConfig $config;
	private IGroupManager $groupManager;

	private AccessService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->service = new AccessService($this->config, $this->groupManager);
	}

	private function mockUser(): IUser {
		return $this->createMock(IUser::class);
	}

	public function testAccessIsAllowedForEveryoneWithoutConfiguredGroups(): void {
		$this->config->method('getAppValue')->willReturnCallback(
			static fn (string $app, string $key, string $default = '') => $key === 'allowed_groups' ? '' : $default,
		);

		$this->assertTrue($this->service->isAccessAllowed($this->mockUser()));
	}

	public function testNullUserIsNotAllowed(): void {
		$this->assertFalse($this->service->isAccessAllowed(null));
	}

	public function testUserInAllowedGroupIsAllowed(): void {
		$this->config->method('getAppValue')->willReturnCallback(
			static fn (string $app, string $key, string $default = '') => $key === 'allowed_groups' ? '["staff"]' : $default,
		);
		$user = $this->mockUser();
		$this->groupManager->method('getUserGroupIds')->with($user)->willReturn(['staff', 'admin']);

		$this->assertTrue($this->service->isAccessAllowed($user));
	}

	public function testUserOutsideAllowedGroupsIsDenied(): void {
		$this->config->method('getAppValue')->willReturnCallback(
			static fn (string $app, string $key, string $default = '') => $key === 'allowed_groups' ? '["staff"]' : $default,
		);
		$user = $this->mockUser();
		$this->groupManager->method('getUserGroupIds')->with($user)->willReturn(['admin']);

		$this->assertFalse($this->service->isAccessAllowed($user));
	}

	public function testInvalidJsonFallsBackToAllowAll(): void {
		$this->config->method('getAppValue')->willReturnCallback(
			static fn (string $app, string $key, string $default = '') => $key === 'allowed_groups' ? '{invalid' : $default,
		);

		$this->assertTrue($this->service->isAccessAllowed($this->mockUser()));
	}

	public function testGetAllowedGroupsFiltersAndNormalizes(): void {
		$this->config->method('getAppValue')->willReturnCallback(
			static fn (string $app, string $key, string $default = '') => $key === 'allowed_groups' ? '["staff", "team", "staff", 1, null, ""]' : $default,
		);

		$this->assertSame(['staff', 'team'], $this->service->getAllowedGroups());
	}
}
