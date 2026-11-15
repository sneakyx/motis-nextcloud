/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'

import './admin-settings.css'

/**
 * Wire up the admin settings form: collect the form values and save them via the settings endpoint.
 */
function init(): void {
	const form = document.getElementById('motis-admin-form') as HTMLFormElement | null
	if (form === null) {
		return
	}

	const baseUrlInput = document.getElementById('motis-base-url') as HTMLInputElement | null
	const apiKeyInput = document.getElementById('motis-api-key') as HTMLInputElement | null
	const timeoutInput = document.getElementById('motis-timeout') as HTMLInputElement | null
	const saveButton = document.getElementById('motis-admin-save') as HTMLButtonElement | null

	form.addEventListener('submit', async (event) => {
		event.preventDefault()

		const allowedGroups = Array.from(form.querySelectorAll<HTMLInputElement>('input[name="allowedGroups[]"]:checked'))
			.map((input) => input.value)

		const data = {
			baseUrl: baseUrlInput?.value.trim() ?? '',
			apiKey: apiKeyInput?.value.trim() ?? '',
			timeout: timeoutInput?.value.trim() ?? '',
			allowedGroups,
		}

		if (saveButton !== null) {
			saveButton.disabled = true
		}

		try {
			await axios.post(generateUrl('/apps/motis/admin-settings'), data)
			showSuccess(t('motis', 'Saved'))
		} catch (error) {
			let message = t('motis', 'Could not save settings')
			if (axios.isAxiosError(error) && typeof error.response?.data?.error === 'string') {
				message = error.response.data.error
			}
			showError(message)
		} finally {
			if (saveButton !== null) {
				saveButton.disabled = false
			}
		}
	})
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => {
		init()
	})
} else {
	init()
}
