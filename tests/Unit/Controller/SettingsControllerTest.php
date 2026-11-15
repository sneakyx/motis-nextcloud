<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Test\Unit\Controller;

use OCA\Motis\Controller\SettingsController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

class SettingsControllerTest extends TestCase {

	private IRequest $request;
	private IConfig $config;
	private IGroupManager $groupManager;
	private IUserSession $userSession;

	/** @var array<string, mixed> */
	private array $params = [];

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->params = [];

		$this->request->method('getParam')->willReturnCallback(
			function (string $key, $default = null) {
				return $this->params[$key] ?? $default;
			},
		);
	}

	private function mockAdminSession(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('admin');
		$this->userSession->method('getUser')->willReturn($user);
		$this->groupManager->method('isAdmin')->with('admin')->willReturn(true);
	}

	public function testUpdateReturns403ForNonAdminUser(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$this->userSession->method('getUser')->willReturn($user);
		$this->groupManager->method('isAdmin')->with('alice')->willReturn(false);

		$controller = new SettingsController($this->request, $this->config, $this->groupManager, $this->userSession);

		$response = $controller->update();

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(403, $response->getStatus());
	}

	public function testUpdateReturns403WithoutUser(): void {
		$this->userSession->method('getUser')->willReturn(null);

		$controller = new SettingsController($this->request, $this->config, $this->groupManager, $this->userSession);

		$response = $controller->update();

		$this->assertSame(403, $response->getStatus());
	}

	public function testUpdateSavesNormalizedSettings(): void {
		$this->mockAdminSession();
		$this->params = [
			'baseUrl' => ' https://motis.example.com ',
			'apiKey' => ' secret ',
			'timeout' => '30',
			'allowedGroups' => ['staff', 'staff', 1, '', null],
		];

		$saved = [];
		$this->config->expects($this->exactly(4))->method('setAppValue')->willReturnCallback(
			static function (string $app, string $key, string $value) use (&$saved): bool {
				$saved[$key] = $value;
				return true;
			},
		);

		$controller = new SettingsController($this->request, $this->config, $this->groupManager, $this->userSession);

		$response = $controller->update();

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['status' => 'success'], $response->getData());
		$this->assertSame('https://motis.example.com', $saved['base_url']);
		$this->assertSame('secret', $saved['api_key']);
		$this->assertSame('30', $saved['timeout']);
		$this->assertSame('["staff"]', $saved['allowed_groups']);
	}

	public function testUpdateRejectsInvalidBaseUrl(): void {
		$this->mockAdminSession();
		$this->params = ['baseUrl' => 'ftp://motis.example.com'];

		$controller = new SettingsController($this->request, $this->config, $this->groupManager, $this->userSession);

		$response = $controller->update();

		$this->assertSame(422, $response->getStatus());
	}

	public function testUpdateRejectsInvalidTimeout(): void {
		$this->mockAdminSession();
		$this->params = ['timeout' => '1000'];

		$controller = new SettingsController($this->request, $this->config, $this->groupManager, $this->userSession);

		$response = $controller->update();

		$this->assertSame(422, $response->getStatus());
	}

	public function testUpdateRejectsNonNumericTimeout(): void {
		$this->mockAdminSession();
		$this->params = ['timeout' => 'abc'];

		$controller = new SettingsController($this->request, $this->config, $this->groupManager, $this->userSession);

		$response = $controller->update();

		$this->assertSame(422, $response->getStatus());
	}
}
