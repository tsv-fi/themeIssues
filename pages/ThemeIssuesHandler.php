<?php

/**
 * @file plugins/generic/themeIssues/pages/ThemeIssuesHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThemeIssuesHandler
 * @ingroup plugins_generic_themeIssues
 *
 * @brief Handle reader-facing router requests
 */

 namespace APP\plugins\generic\themeIssues\pages;

use APP\core\Request;
use APP\template\TemplateManager;
use APP\core\Services;
use APP\facades\Repo;
use APP\issue\Collector;
use PKP\security\authorization\ContextRequiredPolicy;
use APP\security\authorization\OjsJournalMustPublishPolicy;
use PKP\plugins\PluginRegistry;


class ThemeIssuesHandler extends \APP\handler\Handler {

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$this->addPolicy(new ContextRequiredPolicy($request));
		$this->addPolicy(new OjsJournalMustPublishPolicy($request));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * View theme issues
	 */
	public function index($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();
		$plugin = PluginRegistry::getPlugin('generic', 'themeissuesplugin');

        $issues = Repo::issue()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->orderBy(Collector::ORDERBY_SEQUENCE)
			->filterByPublished(true)
            ->getMany();
		
		$themeIssues = [];
		foreach ($issues as $issue) {
			if ($issue->getData('isThemeIssue')){
				$themeIssues[] = $issue;
			}
		}

		$templateMgr->assign(array(
			'issues' => $themeIssues,
		));

		return $templateMgr->display($plugin->getTemplateResource('themeIssues.tpl'));
	}
}
