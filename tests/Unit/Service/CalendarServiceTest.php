<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Test\Unit\Service;

use DateTimeImmutable;
use OCA\Motis\Service\CalendarService;
use OCP\Calendar\ICalendar;
use OCP\Calendar\IManager;
use OCP\IUser;
use PHPUnit\Framework\TestCase;

class CalendarServiceTest extends TestCase {

	private IManager $calendarManager;
	private IUser $user;

	protected function setUp(): void {
		parent::setUp();

		$this->calendarManager = $this->createMock(IManager::class);
		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('testuser');
	}

	private function createService(): CalendarService {
		return new CalendarService($this->calendarManager);
	}

	private function mockCalendar(string $displayName, ?string $color, array $searchResults): ICalendar {
		$calendar = $this->createMock(ICalendar::class);
		$calendar->method('getDisplayName')->willReturn($displayName);
		$calendar->method('getDisplayColor')->willReturn($color);
		$calendar->method('getUri')->willReturn('personal-' . $displayName);
		$calendar->method('search')->willReturn($searchResults);
		return $calendar;
	}

	/**
	 * Build a calendar object as returned by ICalendar::search().
	 *
	 * @param array $overrides properties to override
	 */
	private function eventObject(array $overrides = []): array {
		return array_merge([
			'SUMMARY' => ['Team meeting', []],
			'LOCATION' => ['Marburg Hauptbahnhof', []],
			'DTSTART' => [new DateTimeImmutable('2026-11-16T10:00:00+01:00'), []],
			'DTEND' => [new DateTimeImmutable('2026-11-16T11:00:00+01:00'), []],
		], $overrides);
	}

	public function testReturnsEventsWithLocation(): void {
		$calendar = $this->mockCalendar('Personal', '#0082c9', [
			['type' => 'VEVENT', 'objects' => [$this->eventObject()]],
		]);
		$this->calendarManager->method('getCalendarsForPrincipal')->with('principals/users/testuser')->willReturn([$calendar]);

		$events = $this->createService()->getUpcomingEvents(
			$this->user,
			new DateTimeImmutable('2026-11-15T00:00:00+01:00'),
			new DateTimeImmutable('2026-11-22T00:00:00+01:00'),
			20,
		);

		$this->assertCount(1, $events);
		$this->assertSame('Team meeting', $events[0]['title']);
		$this->assertSame('Marburg Hauptbahnhof', $events[0]['location']);
		$this->assertSame('2026-11-16T10:00:00+01:00', $events[0]['start']);
		$this->assertSame('2026-11-16T11:00:00+01:00', $events[0]['end']);
		$this->assertSame('Personal', $events[0]['calendarName']);
		$this->assertSame('#0082c9', $events[0]['calendarColor']);
	}

	public function testSkipsEventsWithoutLocation(): void {
		$calendar = $this->mockCalendar('Personal', null, [
			['type' => 'VEVENT', 'objects' => [$this->eventObject(['LOCATION' => ['  ', []]])]],
			['type' => 'VEVENT', 'objects' => [$this->eventObject(['LOCATION' => null])]],
		]);
		$this->calendarManager->method('getCalendarsForPrincipal')->willReturn([$calendar]);

		$events = $this->createService()->getUpcomingEvents(
			$this->user,
			new DateTimeImmutable('2026-11-15T00:00:00+01:00'),
			new DateTimeImmutable('2026-11-22T00:00:00+01:00'),
			20,
		);

		$this->assertSame([], $events);
	}

	public function testSkipsObjectsWithoutStartDate(): void {
		$calendar = $this->mockCalendar('Personal', null, [
			['type' => 'VEVENT', 'objects' => [$this->eventObject(['DTSTART' => null])]],
		]);
		$this->calendarManager->method('getCalendarsForPrincipal')->willReturn([$calendar]);

		$events = $this->createService()->getUpcomingEvents(
			$this->user,
			new DateTimeImmutable('2026-11-15T00:00:00+01:00'),
			new DateTimeImmutable('2026-11-22T00:00:00+01:00'),
			20,
		);

		$this->assertSame([], $events);
	}

	public function testSkipsOccurrencesThatAlreadyEnded(): void {
		$calendar = $this->mockCalendar('Personal', null, [
			['type' => 'VEVENT', 'objects' => [
				$this->eventObject([
					'DTSTART' => [new DateTimeImmutable('2026-11-14T09:00:00+01:00'), []],
					'DTEND' => [new DateTimeImmutable('2026-11-14T10:00:00+01:00'), []],
				]),
			]],
		]);
		$this->calendarManager->method('getCalendarsForPrincipal')->willReturn([$calendar]);

		$events = $this->createService()->getUpcomingEvents(
			$this->user,
			new DateTimeImmutable('2026-11-15T00:00:00+01:00'),
			new DateTimeImmutable('2026-11-22T00:00:00+01:00'),
			20,
		);

		$this->assertSame([], $events);
	}

	public function testSkipsNonEventComponents(): void {
		$calendar = $this->mockCalendar('Personal', null, [
			['type' => 'VTODO', 'objects' => [$this->eventObject()]],
		]);
		$this->calendarManager->method('getCalendarsForPrincipal')->willReturn([$calendar]);

		$events = $this->createService()->getUpcomingEvents(
			$this->user,
			new DateTimeImmutable('2026-11-15T00:00:00+01:00'),
			new DateTimeImmutable('2026-11-22T00:00:00+01:00'),
			20,
		);

		$this->assertSame([], $events);
	}

	public function testMergesCalendarsAndSortsByStart(): void {
		$first = $this->mockCalendar('Work', null, [
			['type' => 'VEVENT', 'objects' => [$this->eventObject([
				'SUMMARY' => ['Late event', []],
				'DTSTART' => [new DateTimeImmutable('2026-11-18T10:00:00+01:00'), []],
				'DTEND' => [new DateTimeImmutable('2026-11-18T11:00:00+01:00'), []],
			])]],
		]);
		$second = $this->mockCalendar('Personal', null, [
			['type' => 'VEVENT', 'objects' => [$this->eventObject([
				'SUMMARY' => ['Early event', []],
				'LOCATION' => ['Bad Laasphe Bahnhof', []],
				'DTSTART' => [new DateTimeImmutable('2026-11-16T08:00:00+01:00'), []],
				'DTEND' => [new DateTimeImmutable('2026-11-16T09:00:00+01:00'), []],
			])]],
		]);
		$this->calendarManager->method('getCalendarsForPrincipal')->willReturn([$first, $second]);

		$events = $this->createService()->getUpcomingEvents(
			$this->user,
			new DateTimeImmutable('2026-11-15T00:00:00+01:00'),
			new DateTimeImmutable('2026-11-22T00:00:00+01:00'),
			20,
		);

		$this->assertCount(2, $events);
		$this->assertSame('Early event', $events[0]['title']);
		$this->assertSame('Late event', $events[1]['title']);
	}

	public function testAppliesLimit(): void {
		$objects = [];
		for ($i = 0; $i < 3; $i++) {
			$objects[] = $this->eventObject([
				'DTSTART' => [new DateTimeImmutable('2026-11-16T0' . $i . ':00:00+01:00'), []],
				'DTEND' => [new DateTimeImmutable('2026-11-16T0' . ($i + 1) . ':00:00+01:00'), []],
			]);
		}
		$calendar = $this->mockCalendar('Personal', null, [
			['type' => 'VEVENT', 'objects' => $objects],
		]);
		$this->calendarManager->method('getCalendarsForPrincipal')->willReturn([$calendar]);

		$events = $this->createService()->getUpcomingEvents(
			$this->user,
			new DateTimeImmutable('2026-11-15T00:00:00+01:00'),
			new DateTimeImmutable('2026-11-22T00:00:00+01:00'),
			2,
		);

		$this->assertCount(2, $events);
	}

	public function testUsesCalendarUriWhenDisplayNameIsNull(): void {
		$calendar = $this->createMock(ICalendar::class);
		$calendar->method('getDisplayName')->willReturn(null);
		$calendar->method('getDisplayColor')->willReturn(null);
		$calendar->method('getUri')->willReturn('personal');
		$calendar->method('search')->willReturn([
			['type' => 'VEVENT', 'objects' => [$this->eventObject()]],
		]);
		$this->calendarManager->method('getCalendarsForPrincipal')->willReturn([$calendar]);

		$events = $this->createService()->getUpcomingEvents(
			$this->user,
			new DateTimeImmutable('2026-11-15T00:00:00+01:00'),
			new DateTimeImmutable('2026-11-22T00:00:00+01:00'),
			20,
		);

		$this->assertSame('personal', $events[0]['calendarName']);
	}

	public function testSearchesWithTimeRangeAndTypeFilter(): void {
		$from = new DateTimeImmutable('2026-11-15T00:00:00+01:00');
		$to = new DateTimeImmutable('2026-11-22T00:00:00+01:00');
		$calendar = $this->createMock(ICalendar::class);
		$calendar->method('getDisplayName')->willReturn('Personal');
		$calendar->expects($this->once())->method('search')
			->with('', [], [
				'types' => ['VEVENT'],
				'timerange' => ['start' => $from, 'end' => $to],
			], 40, null)
			->willReturn([]);
		$this->calendarManager->method('getCalendarsForPrincipal')->willReturn([$calendar]);

		$events = $this->createService()->getUpcomingEvents($this->user, $from, $to, 20);

		$this->assertSame([], $events);
	}
}
