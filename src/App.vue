<!--
SPDX-FileCopyrightText: 2026 André Scholz
SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="motis-app">
		<h2>{{ t('motis', 'Motis') }}</h2>

		<RouteSearchForm @search="onSearch" />

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
					:key="itinerary.startTime + '-' + index"
					:itinerary="itinerary"
					:isBest="index === 0" />
			</div>
		</template>
	</div>
</template>

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcLoadingIcon } from '@nextcloud/vue'
import { ref } from 'vue'
import ConnectionCard from './components/ConnectionCard.vue'
import RouteSearchForm from './components/RouteSearchForm.vue'

interface SearchPayload {
	from: { lat: number, lon: number, name: string }
	to: { lat: number, lon: number, name: string }
	time: string | null
	arriveBy: boolean
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
</script>

<style scoped lang="scss">
.motis-app {
	padding: 20px 32px;
}

.motis-app h2 {
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
