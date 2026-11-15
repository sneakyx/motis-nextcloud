<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Service;

use DateTimeInterface;
use Sneakyx\MotisPhpClient\Model\GeocodeMatch;
use Sneakyx\MotisPhpClient\Model\Itinerary;
use Sneakyx\MotisPhpClient\Model\Leg;
use Sneakyx\MotisPhpClient\Model\Place;

/**
 * Thin, serializable facade around the MOTIS client.
 *
 * The normalization methods return plain arrays so the controllers can pass
 * them to JSONResponse directly and the frontend gets a stable, simple shape.
 */
class RoutingService {

	public const GEOCODE_RESULT_LIMIT = 6;

	public function __construct(
		private MotisClientFactory $clientFactory,
	) {
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function geocode(string $text): array {
		$matches = $this->clientFactory->createClient()
			->geocode($text, [], [], null, null, self::GEOCODE_RESULT_LIMIT);

		return array_map($this->normalizeMatch(...), $matches);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function plan(string $from, string $to, ?\DateTimeImmutable $time, bool $arriveBy): array {
		$itineraries = $this->clientFactory->createClient()
			->plan($from, $to, $time, $arriveBy)
			->getItineraries();

		return array_map($this->normalizeItinerary(...), $itineraries ?? []);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function normalizeMatch(GeocodeMatch $match): array {
		return [
			'id' => $match->getId() ?? '',
			'name' => $match->getName() ?? '',
			'lat' => $match->getLat(),
			'lon' => $match->getLon(),
			'type' => $match->getType() ?? 'ADDRESS',
			'street' => $match->getStreet(),
			'houseNumber' => $match->getHouseNumber(),
			'zip' => $match->getZip(),
			'country' => $match->getCountry(),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function normalizeItinerary(Itinerary $itinerary): array {
		return [
			'startTime' => $this->formatTime($itinerary->getStartTime()),
			'endTime' => $this->formatTime($itinerary->getEndTime()),
			'duration' => $itinerary->getDuration() ?? 0,
			'transfers' => $itinerary->getTransfers() ?? 0,
			'legs' => array_map($this->normalizeLeg(...), $itinerary->getLegs() ?? []),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function normalizeLeg(Leg $leg): array {
		return [
			'mode' => (string)($leg->getMode() ?? ''),
			'from' => $this->normalizePlace($leg->getFrom()),
			'to' => $this->normalizePlace($leg->getTo()),
			'startTime' => $this->formatTime($leg->getStartTime()),
			'endTime' => $this->formatTime($leg->getEndTime()),
			'duration' => $leg->getDuration() ?? 0,
			'distance' => $leg->getDistance(),
			'headsign' => $leg->getHeadsign(),
			'routeShortName' => $leg->getRouteShortName(),
			'routeColor' => $leg->getRouteColor(),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function normalizePlace(Place $place): array {
		return [
			'name' => $place->getName() ?? '',
			'lat' => $place->getLat(),
			'lon' => $place->getLon(),
			'stopId' => $place->getStopId(),
		];
	}

	private function formatTime(?\DateTime $time): string {
		return $time?->format(DateTimeInterface::ATOM) ?? '';
	}
}
