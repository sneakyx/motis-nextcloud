<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Controller;

use DateTimeImmutable;
use DateTimeInterface;
use OCA\Motis\AppInfo\Application;
use OCA\Motis\Service\AccessService;
use OCA\Motis\Service\RoutingService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class MotisController extends Controller {

	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private AccessService $accessService,
		private RoutingService $routingService,
		private LoggerInterface $logger,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired, NoCSRFRequired]
	public function geocode(string $query): JSONResponse {
		if (!$this->accessService->isAccessAllowed($this->userSession->getUser())) {
			return new JSONResponse(['error' => 'You are not allowed to use the Motis app.'], 403);
		}

		$query = trim($query);
		if (mb_strlen($query) < 2) {
			return new JSONResponse(['error' => 'Please enter at least 2 characters.'], 400);
		}

		try {
			return new JSONResponse($this->routingService->geocode($query));
		} catch (\Exception $e) {
			$this->logger->warning('Motis geocode failed: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return new JSONResponse(['error' => 'Could not reach the MOTIS server.'], 502);
		}
	}

	#[NoAdminRequired, NoCSRFRequired]
	public function plan(string $from, string $to, ?string $time = null, bool $arriveBy = false): JSONResponse {
		if (!$this->accessService->isAccessAllowed($this->userSession->getUser())) {
			return new JSONResponse(['error' => 'You are not allowed to use the Motis app.'], 403);
		}

		$from = trim($from);
		$to = trim($to);
		if ($from === '' || $to === '') {
			return new JSONResponse(['error' => 'Start and destination are required.'], 400);
		}

		$parsedTime = null;
		if ($time !== null && $time !== '' && $time !== 'now') {
			$parsedTime = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $time) ?: null;
			if ($parsedTime === null) {
				return new JSONResponse(['error' => 'Invalid time format.'], 400);
			}
		}

		try {
			return new JSONResponse(['itineraries' => $this->routingService->plan($from, $to, $parsedTime, $arriveBy)]);
		} catch (\Exception $e) {
			$this->logger->warning('Motis plan failed: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return new JSONResponse(['error' => 'Could not reach the MOTIS server.'], 502);
		}
	}
}
