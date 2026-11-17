<!--
SPDX-FileCopyrightText: 2026 André Scholz
SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<form class="route-search-form" @submit.prevent="onSubmit">
		<div class="route-field">
			<label class="route-label" for="motis-from">{{ t('motis', 'Start') }}</label>
			<NcSelect
				id="motis-from"
				v-model="from"
				:options="fromOptions"
				:loading="loadingFrom"
				:filterable="false"
				:clearable="false"
				label="name"
				:placeholder="t('motis', 'Search for a start location')"
				@search="onSearchFrom">
				<template #option="option">
					<div class="suggestion">
						<span class="type-badge" :class="[option.type === 'STOP' ? 'stop' : 'address']">{{ option.type === 'STOP' ? '🚏' : '📍' }}</span>
						<div>
							<div class="s-main">
								{{ option.name }}
							</div>
							<div class="s-sub">
								{{ optionSubline(option) }}
							</div>
						</div>
					</div>
				</template>
			</NcSelect>
		</div>

		<div class="swap-row">
			<NcButton variant="tertiary" :aria-label="t('motis', 'Swap start and destination')" @click="swap">
				<svg
					width="14"
					height="14"
					viewBox="0 0 12 12"
					fill="currentColor"
					aria-hidden="true">
					<path d="M2 4h7.6L7.3 1.7 8.4.6 11.8 4 8.4 7.4 7.3 6.3 9.6 4H2zM10 8H2.4l2.3 2.3-1.1 1.1L.2 8l3.4-3.4 1.1 1.1L2.4 8H10z" />
				</svg>
				{{ t('motis', 'Swap start and destination') }}
			</NcButton>
		</div>

		<div class="route-field">
			<label class="route-label" for="motis-to">{{ t('motis', 'Destination') }}</label>
			<NcSelect
				id="motis-to"
				v-model="to"
				:options="toOptions"
				:loading="loadingTo"
				:filterable="false"
				:clearable="false"
				label="name"
				:placeholder="t('motis', 'Search for a destination')"
				@search="onSearchTo">
				<template #option="option">
					<div class="suggestion">
						<span class="type-badge" :class="[option.type === 'STOP' ? 'stop' : 'address']">{{ option.type === 'STOP' ? '🚏' : '📍' }}</span>
						<div>
							<div class="s-main">
								{{ option.name }}
							</div>
							<div class="s-sub">
								{{ optionSubline(option) }}
							</div>
						</div>
					</div>
				</template>
			</NcSelect>
		</div>

		<div class="options-row">
			<div class="segmented" role="group" :aria-label="t('motis', 'Time mode')">
				<NcButton :variant="arriveBy ? 'secondary' : 'primary'" @click="arriveBy = false">
					{{ t('motis', 'Departure') }}
				</NcButton>
				<NcButton :variant="arriveBy ? 'primary' : 'secondary'" @click="arriveBy = true">
					{{ t('motis', 'Arrival') }}
				</NcButton>
			</div>

			<NcCheckboxRadioSwitch v-model="useNow" :disabled="false">
				{{ t('motis', 'Now') }}
			</NcCheckboxRadioSwitch>

			<NcDateTimePicker
				v-model="time"
				:disabled="useNow"
				:clearable="false"
				:enableTime="true" />
		</div>

		<NcButton
			variant="primary"
			type="submit"
			class="search-btn"
			:disabled="!canSearch">
			{{ t('motis', 'Search connections') }}
		</NcButton>
	</form>
</template>

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcCheckboxRadioSwitch, NcDateTimePicker, NcSelect } from '@nextcloud/vue'
import { computed, ref } from 'vue'

interface Suggestion {
	id: string
	name: string
	lat: number
	lon: number
	type: string
	street?: string | null
	houseNumber?: string | null
	zip?: string | null
	country?: string | null
}

const emit = defineEmits<{ (e: 'search', payload: { from: Suggestion, to: Suggestion, time: string | null, arriveBy: boolean }): void }>()

const from = ref<Suggestion | null>(null)
const to = ref<Suggestion | null>(null)
const fromOptions = ref<Suggestion[]>([])
const toOptions = ref<Suggestion[]>([])
const loadingFrom = ref(false)
const loadingTo = ref(false)
const arriveBy = ref(false)
const useNow = ref(true)
const time = ref<Date>(new Date())
const typedTooLittle = ref(true)

const canSearch = computed(() => from.value !== null && to.value !== null)

/**
 * Build the subline label for a geocode suggestion.
 *
 * @param option the geocode suggestion to describe
 */
function optionSubline(option: Suggestion): string {
	if (option.type === 'STOP') {
		return t('motis', 'Stop')
	}
	const parts = [option.street, option.houseNumber, option.zip].filter((part) => part !== null && part !== undefined && part !== '')
	return parts.length > 0 ? t('motis', 'Address') + ' · ' + parts.join(' ') : t('motis', 'Address')
}

/**
 * Create a debounced wrapper around the given function.
 *
 * @param fn the function to debounce
 * @param wait the delay in milliseconds
 */
function debounce<T extends (...args: never[]) => void>(fn: T, wait: number): (...args: Parameters<T>) => void {
	let timer: number | undefined
	return (...args: Parameters<T>) => {
		window.clearTimeout(timer)
		timer = window.setTimeout(() => {
			fn(...args)
		}, wait)
	}
}

/**
 * Fetch geocode suggestions for the given search text.
 *
 * @param query the text to search for
 */
async function fetchOptions(query: string): Promise<Suggestion[]> {
	const response = await axios.get<Suggestion[]>(generateUrl('/apps/motis/geocode'), {
		params: { query },
	})
	return response.data
}

const onSearchFrom = debounce(async (query: string) => {
	typedTooLittle.value = query.trim().length < 3
	if (typedTooLittle.value) {
		fromOptions.value = []
		return
	}
	loadingFrom.value = true
	try {
		fromOptions.value = await fetchOptions(query)
	} finally {
		loadingFrom.value = false
	}
}, 300)

const onSearchTo = debounce(async (query: string) => {
	typedTooLittle.value = query.trim().length < 3
	if (typedTooLittle.value) {
		toOptions.value = []
		return
	}
	loadingTo.value = true
	try {
		toOptions.value = await fetchOptions(query)
	} finally {
		loadingTo.value = false
	}
}, 300)

/**
 *
 */
function swap(): void {
	const previousFrom = from.value
	from.value = to.value
	to.value = previousFrom
	const previousOptions = fromOptions.value
	fromOptions.value = toOptions.value
	toOptions.value = previousOptions
}

/**
 *
 */
function onSubmit(): void {
	if (!canSearch.value) {
		return
	}
	emit('search', {
		from: from.value as Suggestion,
		to: to.value as Suggestion,
		time: useNow.value ? null : (time.value as Date).toISOString(),
		arriveBy: arriveBy.value,
	})
}
</script>

<style scoped lang="scss">
.route-search-form {
	max-width: 680px;
}

.route-field {
	margin-block-end: 6px;
}

.route-label {
	display: block;
	font-weight: 600;
	font-size: 13px;
	margin-block-end: 4px;
}

.swap-row {
	display: flex;
	justify-content: flex-end;
	margin-block: 2px;
}

.suggestion {
	display: flex;
	align-items: center;
	gap: 10px;
}

.type-badge {
	width: 30px;
	height: 30px;
	border-radius: 50%;
	flex: none;
	display: grid;
	place-items: center;
	background: var(--color-primary-light);
}

.s-main {
	font-weight: 600;
	font-size: 14px;
}

.s-sub {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

.options-row {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
	align-items: center;
	margin-block-start: 14px;
}

.segmented {
	display: inline-flex;
	gap: 4px;
}

.search-btn {
	margin-block-start: 16px;
}
</style>
