<!--
SPDX-FileCopyrightText: 2026 André Scholz
SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="motis-app">
		<h2>{{ t('motis', 'Motis') }}</h2>
		<NcEmptyContent
			:name="t('motis', 'Public transit routing')"
			:description="t('motis', 'The route search UI and the calendar and contacts integration will be added in the next milestones.')" />
	</div>
</template>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { NcEmptyContent } from '@nextcloud/vue'
</script>

<style scoped lang="scss">
.motis-app {
	padding: 20px 32px;
}

.motis-app h2 {
	margin-bottom: 16px;
}
</style>
