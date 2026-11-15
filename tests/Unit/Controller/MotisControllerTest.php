<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Test\Unit\Controller;

use OCA\Motis\Controller\MotisController;
use OCA\Motis\Service\AccessService;
use OCA\Motis\Service\RoutingService;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MotisControllerTest extends TestCase {

	private IRequest $request;
	private IUserSession $userSession;
	private AccessService $accessService;
	private RoutingService $routingService;
	private LoggerInterface $logger;
	private IUser $user;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->accessService = $this->createMock(AccessService::class);
		$this->routingService = $this->createMock(RoutingService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->user = $this->createMock(IUser::class);

		$this->userSession->method('getUser')->willReturn($this->user);
	}

	private function createController(): MotisController {
		return new MotisController(
			$this->request,
			$this->userSession,
			$this->accessService,
			$this->routingService,
			$this->logger,
		);
	}

	public function testGeocodeReturns403WhenAccessDenied(): void {
		$this->accessService->method('isAccessAllowed')->with($this->user)->willReturn(false);

		$response = $this->createController()->geocode('Marburg');

		$this->assertSame(403, $response->getStatus());
	}

	public function testGeocodeRejectsTooShortQuery(): void {
		$this->accessService->method('isAccessAllowed')->with($this->user)->willReturn(true);

		$response = $this->createController()->geocode('M');

		$this->assertSame(400, $response->getStatus());
	}

	public function testGeocodeReturnsMatches(): void {
		$this->accessService->method('isAccessAllowed')->with($this->user)->willReturn(true);
		$matches = [['name' => 'Marburg Hauptbahnhof', 'lat' => 50.81]];
		$this->routingService->method('geocode')->with('Marburg Hbf')->willReturn($matches);

		$response = $this->createController()->geocode(' Marburg Hbf ');

		$this->assertSame(200, $response->getStatus());
		$this->assertSame($matches, $response->getData());
	}

	public function testPlanReturns403WhenAccessDenied(): void {
		$this->accessService->method('isAccessAllowed')->with($this->user)->willReturn(false);

		$response = $this->createController()->plan('50.81,8.77', '50.92,8.44');

		$this->assertSame(403, $response->getStatus());
	}

	public function testPlanRequiresFromAndTo(): void {
		$this->accessService->method('isAccessAllowed')->with($this->user)->willReturn(true);

		$this->assertSame(400, $this->createController()->plan('', '50.92,8.44')->getStatus());
		$this->assertSame(400, $this->createController()->plan('50.81,8.77', ' ')->getStatus());
	}

	public function testPlanRejectsInvalidTime(): void {
		$this->accessService->method('isAccessAllowed')->with($this->user)->willReturn(true);

		$response = $this->createController()->plan('50.81,8.77', '50.92,8.44', 'not-a-time');

		$this->assertSame(400, $response->getStatus());
	}

	public function testPlanReturnsItineraries(): void {
		$this->accessService->method('isAccessAllowed')->with($this->user)->willReturn(true);
		$itineraries = [['duration' => 4620]];
		$this->routingService->method('plan')->willReturnCallback(
			function (string $from, string $to, $time, bool $arriveBy) use ($itineraries): array {
				$this->assertSame('50.81,8.77', $from);
				$this->assertSame('50.92,8.44', $to);
				$this->assertNull($time);
				$this->assertFalse($arriveBy);
				return $itineraries;
			},
		);

		$response = $this->createController()->plan('50.81,8.77', '50.92,8.44', 'now');

		$this->assertSame(200, $response->getStatus());
		$this->assertSame(['itineraries' => $itineraries], $response->getData());
	}

	public function testPlanPassesParsedTime(): void {
		$this->accessService->method('isAccessAllowed')->with($this->user)->willReturn(true);
		$this->routingService->method('plan')->willReturnCallback(
			function (string $from, string $to, $time, bool $arriveBy): array {
				$this->assertNotNull($time);
				$this->assertSame('2026-11-16T10:00:00+00:00', $time->format('c'));
				$this->assertTrue($arriveBy);
				return [];
			},
		);

		$response = $this->createController()->plan('50.81,8.77', '50.92,8.44', '2026-11-16T10:00:00+00:00', true);

		$this->assertSame(200, $response->getStatus());
	}

	public function testGeocodeReturns502OnServerError(): void {
		$this->accessService->method('isAccessAllowed')->with($this->user)->willReturn(true);
		$this->routingService->method('geocode')->willThrowException(new \Exception('upstream error'));

		$response = $this->createController()->geocode('Marburg');

		$this->assertSame(502, $response->getStatus());
	}
}
