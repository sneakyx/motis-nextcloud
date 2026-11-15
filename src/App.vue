<!--
SPDX-FileCopyrightText: 2026 André Scholz
SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="motis-app">
		<h2>{{ t('motis', 'Motis') }}</h2>

		<div class="tab-bar" role="tablist">
			<NcButton :variant="activeTab === 'route' ? 'secondary' : 'tertiary'" @click="activeTab = 'route'">
				{{ t('motis', 'Connections') }}
			</NcButton>
			<NcButton :variant="activeTab === 'calendar' ? 'secondary' : 'tertiary'" @click="activeTab = 'calendar'">
				{{ t('motis', 'Calendar') }}
			</NcButton>
		</div>

		<div v-if="activeTab === 'route'">
			<RouteSearchForm :prefill="prefill" @search="onSearch" @prefillApplied="onPrefillApplied" />

			<section v-if="loading" class="results">
				<NcLoadingIcon :name="t('motis', 'Searching connections')" :size="32" />
			</section>

			<template v-else-if="results !== null">
				<h3 v-if="results.length === 0" class="no-results">
					{{ t('motis', 'No connections found') }}
				</h3>
				<div v-else class="results">
					<ConnectionCard
						v-for="(itinerary, index) in results"
						:key="index"
						:itinerary="itinerary"
						:isBest="index === 0" />
				</div>
			</template>
		</div>

		<EventsList v-else @routeTo="onRouteToEvent" />
	</div>
</template>

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import { ref } from 'vue'
import ConnectionCard from './components/ConnectionCard.vue'
import EventsList from './components/EventsList.vue'
import RouteSearchForm from './components/RouteSearchForm.vue'

interface SearchPayload {
	from: { lat: number, lon: number, name: string }
	to: { lat: number, lon: number, name: string }
	time: string | null
	arriveBy: boolean
}

interface GeoResult {
	id: string
	name: string
	lat: number
	lon: number
	type: string
}

interface CalendarEvent {
	title: string
	location: string
	start: string
	end: string | null
	calendarName: string
	calendarColor: string | null
}

interface Leg {
	mode: string
	from: { name: string }
	to: { name: string }
	duration: number
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

const results = ref<Itinerary[] | null>(null)
const loading = ref(false)
const activeTab = ref<'route' | 'calendar'>('route')
const prefill = ref<{ destination: GeoResult, time: Date } | null>(null)

/**
 * Run a connection search and display the returned itineraries.
 *
 * @param payload the search request from the route search form
 */
async function onSearch(payload: SearchPayload): Promise<void> {
	loading.value = true
	try {
		const response = await axios.get<{ itineraries: Itinerary[] }>(generateUrl('/apps/motis/plan'), {
			params: {
				from: payload.from.lat + ',' + payload.from.lon,
				to: payload.to.lat + ',' + payload.to.lon,
				time: payload.time ?? 'now',
				arriveBy: payload.arriveBy ? '1' : '0',
			},
		})
		results.value = response.data.itineraries
		if (response.data.itineraries.length === 0) {
			showSuccess(t('motis', 'No connections found'))
		}
	} catch {
		showError(t('motis', 'Could not load connections. Please try again.'))
	} finally {
		loading.value = false
	}
}

/**
 * Geocode the location of a calendar event and prefill the route search.
 *
 * @param event the calendar event selected in the events list
 */
async function onRouteToEvent(event: CalendarEvent): Promise<void> {
	activeTab.value = 'route'
	loading.value = true
	try {
		const response = await axios.get<GeoResult[]>(generateUrl('/apps/motis/geocode'), {
			params: { query: event.location },
		})
		if (response.data.length === 0) {
			showError(t('motis', 'Could not find the event location.'))
			return
		}
		prefill.value = { destination: response.data[0], time: new Date(event.start) }
	} catch {
		showError(t('motis', 'Could not load connections. Please try again.'))
	} finally {
		loading.value = false
	}
}

/** Clear the consumed prefill payload. */
function onPrefillApplied(): void {
	prefill.value = null
}
</script>

<style scoped lang="scss">
.motis-app {
	padding: 20px 32px;
}

.motis-app h2 {
	margin-block-end: 16px;
}

.tab-bar {
	display: flex;
	gap: 4px;
	margin-block-end: 16px;
}

.results {
	margin-block-start: 28px;
}

.no-results {
	color: var(--color-text-maxcontrast);
	font-weight: 400;
}
</style>
