<!--
SPDX-FileCopyrightText: 2026 André Scholz
SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<article class="connection-card" :class="[{ best: isBest }]">
		<div class="result-top">
			<span class="r-times">{{ formatTime(itinerary.startTime) }} – <span class="arrive">{{ formatTime(itinerary.endTime) }}</span></span>
			<span class="r-duration">{{ formatDuration(itinerary.duration) }}</span>
			<span v-if="isBest" class="badge best">{{ t('motis', 'Best connection') }}</span>
			<span v-if="itinerary.transfers > 0" class="badge transfers">
				{{ n('motis', '%n transfer', '%n transfers', itinerary.transfers) }}
			</span>
		</div>
		<div class="result-legs">
			<template v-for="(leg, index) in itinerary.legs" :key="index">
				<span class="mini-dot" />
				<span v-if="leg.mode === 'WALK'" class="leg">🚶 {{ formatDuration(leg.duration) }}</span>
				<span v-else class="leg">
					{{ modeEmoji(leg.mode) }}
					<template v-if="leg.routeShortName">{{ leg.routeShortName }} · </template>{{ leg.from.name }} → {{ leg.to.name }}
				</span>
				<span
					v-if="index < itinerary.legs.length - 1"
					class="leg-line"
					:style="{ background: legColor(leg) }" />
			</template>
			<span class="mini-dot" />
		</div>
	</article>
</template>

<script setup lang="ts">
import { n, t } from '@nextcloud/l10n'

interface Place {
	name: string
	lat: number | null
	lon: number | null
}

interface Leg {
	mode: string
	from: Place
	to: Place
	startTime: string
	endTime: string
	duration: number
	distance: number | null
	headsign: string | null
	routeShortName: string | null
	routeColor: string | null
}

interface Itinerary {
	startTime: string
	endTime: string
	duration: number
	transfers: number
	legs: Leg[]
}

defineProps<{
	/** One normalized connection itinerary as returned by the plan endpoint. */
	itinerary: Itinerary
	/** Whether this connection is the first (best) result of the search. */
	isBest: boolean
}>()

/**
 * Format an ISO timestamp as local time of day.
 *
 * @param iso the ISO 8601 timestamp to format
 */
function formatTime(iso: string): string {
	return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

/**
 * Format a duration given in seconds as human readable minutes/hours.
 *
 * @param seconds the duration in seconds
 */
function formatDuration(seconds: number): string {
	const minutes = Math.round(seconds / 60)
	if (minutes < 60) {
		return minutes + ' min'
	}
	const hours = Math.floor(minutes / 60)
	const rest = minutes % 60
	if (rest === 0) {
		return hours + ' h'
	}
	return hours + ' h ' + rest + ' min'
}

/**
 * Get a matching emoji for a MOTIS leg mode.
 *
 * @param mode the MOTIS mode name, e.g. WALK or BUS
 */
function modeEmoji(mode: string): string {
	const map: Record<string, string> = {
		WALK: '🚶',
		BIKE: '🚲',
		BUS: '🚌',
		COACH: '🚌',
		TRAM: '🚋',
		SUBWAY: '🚇',
		SUBURBAN: '🚈',
		RAIL: '🚆',
		HIGHSPEED_RAIL: '🚆',
		LONG_DISTANCE: '🚆',
		NIGHT_RAIL: '🚆',
		REGIONAL_RAIL: '🚆',
		FERRY: '⛴️',
		AIRPLANE: '✈️',
		FUNICULAR: '🚟',
		AERIAL_LIFT: '🚡',
		CABLE_CAR: '🚡',
		CAR: '🚗',
	}
	return map[mode] ?? '🚍'
}

/**
 * Get the line color for a leg, preferring the route color from MOTIS.
 *
 * @param leg the leg to get the color for
 */
function legColor(leg: Leg): string {
	if (leg.routeColor !== null && leg.routeColor !== undefined && leg.routeColor !== '') {
		return '#' + leg.routeColor.replace('#', '')
	}
	if (leg.mode === 'WALK') {
		return 'var(--color-success)'
	}
	return 'var(--color-primary)'
}
</script>

<style scoped lang="scss">
.connection-card {
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 14px 16px;
	margin-block-end: 10px;
	background-color: var(--color-main-background);
	cursor: pointer;

	&:hover {
		border-color: var(--color-primary);
	}

	&.best {
		border-color: var(--color-primary);
	}
}

.result-top {
	display: flex;
	align-items: baseline;
	gap: 12px;
	flex-wrap: wrap;
}

.r-times {
	font-weight: 700;
	font-size: 16px;

	.arrive {
		color: var(--color-text-maxcontrast);
		font-weight: 600;
	}
}

.r-duration {
	color: var(--color-text-maxcontrast);
	font-size: 13px;
}

.badge {
	border-radius: 999px;
	font-size: 11px;
	padding: 2px 10px;
	font-weight: 600;

	&.best {
		background: var(--color-primary-light);
		color: var(--color-primary-text);
	}

	&.transfers {
		background: var(--color-background-dark);
		color: var(--color-text-maxcontrast);
	}
}

.result-legs {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-block-start: 10px;
	flex-wrap: wrap;
}

.leg {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

.leg-line {
	display: inline-block;
	width: 34px;
	height: 4px;
	border-radius: 2px;
}

.mini-dot {
	width: 9px;
	height: 9px;
	border-radius: 50%;
	background: var(--color-text-maxcontrast);
	flex: none;
}
</style>
