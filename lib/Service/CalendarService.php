<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Service;

use DateTimeImmutable;
use DateTimeInterface;
use OCP\Calendar\ICalendar;
use OCP\Calendar\IManager;
use OCP\IUser;

class CalendarService {

	public function __construct(
		private IManager $calendarManager,
	) {
	}

	/**
	 * Load the upcoming events with a location for the given user.
	 *
	 * @param IUser $user the user whose calendars are searched
	 * @param DateTimeImmutable $from the start of the requested time range
	 * @param DateTimeImmutable $to the end of the requested time range
	 * @param int $limit the maximum number of events to return
	 *
	 * @return list<array{title: string, location: string, start: string, end: string|null, calendarName: string, calendarColor: string|null}>
	 */
	public function getUpcomingEvents(IUser $user, DateTimeImmutable $from, DateTimeImmutable $to, int $limit): array {
		$calendars = $this->calendarManager->getCalendarsForPrincipal('principals/users/' . $user->getUID());

		$events = [];
		foreach ($calendars as $calendar) {
			$results = $calendar->search('', [], [
				'types' => ['VEVENT'],
				'timerange' => ['start' => $from, 'end' => $to],
			], max($limit * 2, 25), null);

			foreach ($results as $result) {
				if (($result['type'] ?? null) !== 'VEVENT') {
					continue;
				}
				foreach ($result['objects'] ?? [] as $object) {
					$event = $this->normalizeEvent($object, $calendar, $from);
					if ($event !== null) {
						$events[] = $event;
					}
				}
			}
		}

		usort($events, static fn (array $a, array $b): int => $a['sortKey'] <=> $b['sortKey']);

		$events = array_slice($events, 0, $limit);

		return array_map(static fn (array $event): array => array_diff_key($event, ['sortKey' => true]), $events);
	}

	/**
	 * Normalize a calendar object into an event array.
	 *
	 * Events without a location or without a start date are skipped, as well
	 * as occurrences that already ended before the start of the time range.
	 *
	 * @param array $object the calendar object as returned by ICalendar::search()
	 * @param ICalendar $calendar the calendar the object belongs to
	 * @param DateTimeImmutable $from the start of the requested time range
	 *
	 * @return array|null the normalized event or null if the object is not relevant
	 */
	private function normalizeEvent(array $object, ICalendar $calendar, DateTimeImmutable $from): ?array {
		$start = $object['DTSTART'][0] ?? null;
		$end = $object['DTEND'][0] ?? null;
		$location = $object['LOCATION'][0] ?? null;

		if (!$start instanceof DateTimeInterface) {
			return null;
		}
		if (!is_string($location) || trim($location) === '') {
			return null;
		}
		if ($end instanceof DateTimeInterface && $end <= $from) {
			return null;
		}

		$title = $object['SUMMARY'][0] ?? null;

		return [
			'title' => is_string($title) ? $title : '',
			'location' => trim($location),
			'start' => $start->format(DateTimeInterface::ATOM),
			'end' => $end instanceof DateTimeInterface ? $end->format(DateTimeInterface::ATOM) : null,
			'calendarName' => $calendar->getDisplayName() ?? $calendar->getUri(),
			'calendarColor' => $calendar->getDisplayColor(),
			'sortKey' => $start->getTimestamp(),
		];
	}
}
