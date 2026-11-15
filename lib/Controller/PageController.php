<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Scholz
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Motis\Controller;

use OCA\Motis\AppInfo\Application;
use OCA\Motis\Service\AccessService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;

class PageController extends Controller {

	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private AccessService $accessService,
		private IL10N $l,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index(): TemplateResponse {
		$user = $this->userSession->getUser();

		if (!$this->accessService->isAccessAllowed($user)) {
			$response = new TemplateResponse(Application::APP_ID, 'no-access', [
				'message' => $this->l->t('You are not allowed to use the Motis app. Please ask an administrator for access.'),
			]);
			$response->setStatus(403);
			return $response;
		}

		return new TemplateResponse(Application::APP_ID, 'main');
	}
}
