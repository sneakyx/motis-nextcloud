<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Test\Unit\Service;

use DateTime;
use OCA\Motis\Service\MotisClientFactory;
use OCA\Motis\Service\RoutingService;
use PHPUnit\Framework\TestCase;
use Sneakyx\MotisPhpClient\Model\GeocodeMatch;
use Sneakyx\MotisPhpClient\Model\Itinerary;
use Sneakyx\MotisPhpClient\Model\Leg;
use Sneakyx\MotisPhpClient\Model\Place;

class RoutingServiceTest extends TestCase {

	private RoutingService $service;

	protected function setUp(): void {
		parent::setUp();

		$factory = $this->createMock(MotisClientFactory::class);
		$this->service = new RoutingService($factory);
	}

	public function testNormalizeMatch(): void {
		$match = new GeocodeMatch([
			'id' => 'de:06537:20478',
			'name' => 'Bad Laasphe Bahnhof',
			'lat' => 50.9222,
			'lon' => 8.4464,
			'type' => 'STOP',
			'street' => 'Am Bahnhof',
			'house_number' => '1',
			'zip' => '35232',
			'country' => 'DE',
		]);

		$result = $this->service->normalizeMatch($match);

		$this->assertSame('de:06537:20478', $result['id']);
		$this->assertSame('Bad Laasphe Bahnhof', $result['name']);
		$this->assertSame(50.9222, $result['lat']);
		$this->assertSame(8.4464, $result['lon']);
		$this->assertSame('STOP', $result['type']);
		$this->assertSame('Am Bahnhof', $result['street']);
		$this->assertSame('35232', $result['zip']);
	}

	public function testNormalizeMatchDefaults(): void {
		$match = new GeocodeMatch();

		$result = $this->service->normalizeMatch($match);

		$this->assertSame('', $result['id']);
		$this->assertSame('', $result['name']);
		$this->assertSame('ADDRESS', $result['type']);
	}

	public function testNormalizeItinerary(): void {
		$from = new Place(['name' => 'Marburg Hauptbahnhof', 'lat' => 50.8103, 'lon' => 8.7775]);
		$to = new Place(['name' => 'Bad Laasphe Bahnhof', 'lat' => 50.9222, 'lon' => 8.4464]);

		$leg = new Leg([
			'mode' => 'BUS',
			'from' => $from,
			'to' => $to,
			'start_time' => new DateTime('2026-11-16 10:28:00'),
			'end_time' => new DateTime('2026-11-16 11:45:00'),
			'duration' => 4620,
			'distance' => 42.5,
			'headsign' => 'Bad Laasphe',
		]);

		$itinerary = new Itinerary([
			'start_time' => new DateTime('2026-11-16 10:28:00'),
			'end_time' => new DateTime('2026-11-16 11:45:00'),
			'duration' => 4620,
			'transfers' => 1,
			'legs' => [$leg],
		]);

		$result = $this->service->normalizeItinerary($itinerary);

		$this->assertSame('2026-11-16T10:28:00+00:00', $result['startTime']);
		$this->assertSame('2026-11-16T11:45:00+00:00', $result['endTime']);
		$this->assertSame(4620, $result['duration']);
		$this->assertSame(1, $result['transfers']);
		$this->assertCount(1, $result['legs']);

		$normalizedLeg = $result['legs'][0];
		$this->assertSame('BUS', $normalizedLeg['mode']);
		$this->assertSame('Marburg Hauptbahnhof', $normalizedLeg['from']['name']);
		$this->assertSame('Bad Laasphe Bahnhof', $normalizedLeg['to']['name']);
		$this->assertSame('Bad Laasphe', $normalizedLeg['headsign']);
		$this->assertSame(4620, $normalizedLeg['duration']);
	}

	public function testNormalizeItineraryToleratesNullLegs(): void {
		$itinerary = new Itinerary(['duration' => 100, 'transfers' => 0]);

		$result = $this->service->normalizeItinerary($itinerary);

		$this->assertSame(100, $result['duration']);
		$this->assertSame(0, $result['transfers']);
		$this->assertSame([], $result['legs']);
	}
}
