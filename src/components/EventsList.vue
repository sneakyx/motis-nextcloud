<!--
SPDX-FileCopyrightText: 2026 André Scholz
SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="events-list">
		<div class="events-header">
			<h3 class="events-title">
				{{ t('motis', 'Upcoming events') }}
			</h3>
			<NcButton variant="tertiary" :aria-label="t('motis', 'Refresh events')" @click="loadEvents">
				<svg
					viewBox="0 0 16 16"
					width="16"
					height="16"
					fill="currentColor"
					aria-hidden="true">
					<path d="M8 0a8 8 0 1 0 8 8 8 8 0 0 0-8-8zm0 14a6 6 0 1 1 6-6 6 6 0 0 1-6 6zm3.3-9.3-3.8 1.5a1 1 0 0 0-.5.5l-1.5 3.8a1 1 0 0 0 1.3 1.3l3.8-1.5a1 1 0 0 0 .5-.5l1.5-3.8a1 1 0 0 0-1.3-1.3z" />
				</svg>
			</NcButton>
		</div>

		<NcLoadingIcon
			v-if="loading"
			class="events-loading"
			:name="t('motis', 'Loading events')"
			:size="32" />

		<NcEmptyContent
			v-else-if="events.length === 0"
			:name="t('motis', 'No events with a location found')"
			:description="t('motis', 'Events from your calendars with a location appear here.')">
			<template #icon>
				<svg
					viewBox="0 0 16 16"
					width="32"
					height="32"
					fill="currentColor"
					aria-hidden="true">
					<path d="M13.5 2h-2V1a1 1 0 0 0-2 0v1H5V1a1 1 0 0 0-2 0v1H1.5A1.5 1.5 0 0 0 0 3.5v11A1.5 1.5 0 0 0 1.5 16h12a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 13.5 2zm.5 12.5H1V5h13zM3 7h2v2H3zm3 0h2v2H6zm3 0h2v2H9zM3 10h2v2H3zm3 0h2v2H6zm3 0h2v2H9z" />
				</svg>
			</template>
		</NcEmptyContent>

		<ul v-else class="event-items">
			<li v-for="event in events" :key="event.start + event.title" class="event-item">
				<span class="event-dot" :style="{ backgroundColor: event.calendarColor ?? 'var(--color-primary)' }" />
				<div class="event-main">
					<div class="event-name">
						{{ event.title !== '' ? event.title : t('motis', 'Untitled event') }}
					</div>
					<div class="event-location">
						{{ event.location }}
					</div>
					<div class="event-time">
						{{ formatEventTime(event) }}
					</div>
				</div>
				<NcButton variant="secondary" @click="emit('routeTo', event)">
					{{ t('motis', 'Show route') }}
				</NcButton>
			</li>
		</ul>
	</div>
</template>

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcEmptyContent, NcLoadingIcon } from '@nextcloud/vue'
import { onMounted, ref } from 'vue'

interface CalendarEvent {
	title: string
	location: string
	start: string
	end: string | null
	calendarName: string
	calendarColor: string | null
}

const emit = defineEmits<{
	(e: 'routeTo', event: CalendarEvent): void
}>()

const events = ref<CalendarEvent[]>([])
const loading = ref(false)

/**
 * Format the start time of an event for display in the list.
 *
 * @param event the calendar event to format
 */
function formatEventTime(event: CalendarEvent): string {
	const start = new Date(event.start)
	const date = start.toLocaleDateString(undefined, { weekday: 'short', day: '2-digit', month: '2-digit' })
	const time = start.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
	return date + ', ' + time
}

/** Load the upcoming calendar events from the backend. */
async function loadEvents(): Promise<void> {
	loading.value = true
	try {
		const response = await axios.get<CalendarEvent[]>(generateUrl('/apps/motis/events'), {
			params: { limit: 20 },
		})
		events.value = response.data
	} catch {
		showError(t('motis', 'Could not load events.'))
	} finally {
		loading.value = false
	}
}

onMounted(() => {
	loadEvents()
})
</script>

<style scoped>
.events-list {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.events-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
}

.events-title {
	margin: 0;
	font-size: 20px;
	font-weight: bold;
}

.events-loading {
	margin: 32px auto;
}

.event-items {
	display: flex;
	flex-direction: column;
	gap: 8px;
	margin: 0;
	padding: 0;
	list-style: none;
}

.event-item {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 12px;
	border-radius: var(--border-radius-large);
	background-color: var(--color-background-hover);
}

.event-dot {
	flex-shrink: 0;
	width: 12px;
	height: 12px;
	border-radius: 50%;
}

.event-main {
	flex: 1;
	min-width: 0;
}

.event-name {
	font-weight: bold;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.event-location {
	color: var(--color-text-maxcontrast);
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.event-time {
	color: var(--color-text-maxcontrast);
	font-size: 12px;
}
</style>
