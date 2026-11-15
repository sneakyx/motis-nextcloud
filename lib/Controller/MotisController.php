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
use OCA\Motis\Service\CalendarService;
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
		private CalendarService $calendarService,
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

	#[NoAdminRequired, NoCSRFRequired]
	public function events(?string $from = null, ?string $to = null, ?string $limit = null): JSONResponse {
		if (!$this->accessService->isAccessAllowed($this->userSession->getUser())) {
			return new JSONResponse(['error' => 'You are not allowed to use the Motis app.'], 403);
		}

		$now = new DateTimeImmutable();
		$fromDate = $this->parseIsoDate($from, $now);
		if ($fromDate === null) {
			return new JSONResponse(['error' => 'Invalid "from" date. Use ISO 8601.'], 400);
		}

		$toDate = $this->parseIsoDate($to, $fromDate->modify('+7 days'));
		if ($toDate === null) {
			return new JSONResponse(['error' => 'Invalid "to" date. Use ISO 8601.'], 400);
		}
		if ($toDate <= $fromDate) {
			return new JSONResponse(['error' => 'The end date must be after the start date.'], 400);
		}

		$parsedLimit = $limit === null ? 20 : (ctype_digit($limit) ? (int)$limit : 0);
		if ($parsedLimit < 1 || $parsedLimit > 100) {
			return new JSONResponse(['error' => 'The limit must be between 1 and 100.'], 400);
		}

		try {
			return new JSONResponse([
				'events' => $this->calendarService->getUpcomingEvents($this->userSession->getUser(), $fromDate, $toDate, $parsedLimit),
			]);
		} catch (\Exception $e) {
			$this->logger->warning('Motis events failed: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return new JSONResponse(['error' => 'Could not load the calendar events.'], 500);
		}
	}

	/**
	 * Parse an ISO 8601 date, returning the fallback for empty values.
	 *
	 * @param string|null $value the date string to parse
	 * @param DateTimeImmutable $fallback the value to use when the input is empty
	 *
	 * @return DateTimeImmutable|null the parsed date or null if the input is invalid
	 */
	private function parseIsoDate(?string $value, DateTimeImmutable $fallback): ?DateTimeImmutable {
		if ($value === null || trim($value) === '') {
			return $fallback;
		}
		return DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, trim($value)) ?: null;
	}
}
