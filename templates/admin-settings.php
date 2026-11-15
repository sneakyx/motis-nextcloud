<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

style('motis', 'motis-admin-settings');
/** @var array $_ */
script('motis', 'motis-admin-settings');
?>
<div class="motis-admin-settings" id="motis-admin-settings">
	<h2><?php p($_['tr']['pageTitle']); ?></h2>
	<p class="motis-admin-intro"><?php p($_['tr']['intro']); ?></p>

	<form id="motis-admin-form" class="motis-admin-form">
		<label class="motis-admin-label" for="motis-base-url"><?php p($_['tr']['baseUrlLabel']); ?></label>
		<input type="text" id="motis-base-url" name="baseUrl"
			placeholder="https://api.transitous.org"
			value="<?php p($_['baseUrl']); ?>" />
		<p class="motis-admin-hint"><?php p($_['tr']['baseUrlHint']); ?></p>

		<label class="motis-admin-label" for="motis-api-key"><?php p($_['tr']['apiKeyLabel']); ?></label>
		<input type="password" id="motis-api-key" name="apiKey"
			placeholder=""
			value="<?php p($_['apiKey']); ?>" autocomplete="off" />
		<p class="motis-admin-hint"><?php p($_['tr']['apiKeyHint']); ?></p>

		<label class="motis-admin-label" for="motis-timeout"><?php p($_['tr']['timeoutLabel']); ?></label>
		<input type="text" id="motis-timeout" name="timeout"
			placeholder="30"
			value="<?php p($_['timeout']); ?>" />
		<p class="motis-admin-hint"><?php p($_['tr']['timeoutHint']); ?></p>

		<label class="motis-admin-label"><?php p($_['tr']['allowedGroupsLabel']); ?></label>
		<div class="motis-admin-groups">
			<?php foreach ($_['groups'] as $group): ?>
				<label class="motis-admin-group">
					<input type="checkbox" name="allowedGroups[]"
						value="<?php p($group['id']); ?>"
						<?php if (in_array($group['id'], $_['allowedGroups'], true)): ?>checked<?php endif; ?> />
					<?php p($group['displayName']); ?>
				</label>
			<?php endforeach; ?>
		</div>
		<p class="motis-admin-hint"><?php p($_['tr']['allowedGroupsHint']); ?></p>

		<button type="submit" class="button primary" id="motis-admin-save"><?php p($_['tr']['saveLabel']); ?></button>
		<span id="motis-admin-status" class="motis-admin-status"></span>
	</form>
</div>
