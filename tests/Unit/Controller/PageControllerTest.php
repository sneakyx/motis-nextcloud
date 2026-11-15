<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Test\Unit\Controller;

use OCA\Motis\Controller\PageController;
use OCA\Motis\Service\AccessService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

class PageControllerTest extends TestCase {

	private IRequest $request;
	private IUserSession $userSession;
	private AccessService $accessService;
	private IL10N $l;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->accessService = $this->createMock(AccessService::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l->method('t')->willReturnCallback(static fn (string $text) => $text);
	}

	public function testIndexRendersMainTemplateForAllowedUser(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->method('getUser')->willReturn($user);
		$this->accessService->method('isAccessAllowed')->with($user)->willReturn(true);

		$controller = new PageController($this->request, $this->userSession, $this->accessService, $this->l);

		$response = $controller->index();

		$this->assertInstanceOf(TemplateResponse::class, $response);
		$this->assertSame('main', $response->getTemplateName());
		$this->assertSame(200, $response->getStatus());
	}

	public function testIndexReturns403ForNotAllowedUser(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->method('getUser')->willReturn($user);
		$this->accessService->method('isAccessAllowed')->with($user)->willReturn(false);

		$controller = new PageController($this->request, $this->userSession, $this->accessService, $this->l);

		$response = $controller->index();

		$this->assertInstanceOf(TemplateResponse::class, $response);
		$this->assertSame('no-access', $response->getTemplateName());
		$this->assertSame(403, $response->getStatus());
		$this->assertArrayHasKey('message', $response->getParams());
	}

	public function testIndexReturns403WithoutUser(): void {
		$this->userSession->method('getUser')->willReturn(null);

		$controller = new PageController($this->request, $this->userSession, $this->accessService, $this->l);

		$response = $controller->index();

		$this->assertSame('no-access', $response->getTemplateName());
		$this->assertSame(403, $response->getStatus());
	}
}
