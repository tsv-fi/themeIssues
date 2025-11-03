<?php

/**
 * @file plugins/generic/themeIssues/ThemeIssuesPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ThemeIssuesPlugin
 * @ingroup plugins_generic_themeIssues
 *
 * @brief ThemeIssues plugin class
 */

 namespace APP\plugins\generic\themeIssues;

 use PKP\plugins\GenericPlugin;
 use APP\plugins\generic\themeIssues\pages\ThemeIssuesHandler;
 use PKP\plugins\Hook;
 use APP\facades\Repo;

class ThemeIssuesPlugin extends GenericPlugin {

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path, $mainContextId = NULL) {
		$success = parent::register($category, $path);
		if ($success && $this->getEnabled()) {

			Hook::Add('LoadHandler', array($this, 'loadPageHandler'));

			// Handle issue form
			Hook::Add('Templates::Editor::Issues::IssueData::AdditionalMetadata', array($this, 'addIssueFormFields'));
			Hook::Add('issuedao::getAdditionalFieldNames', array($this, 'addIssueDAOFieldNames'));
			Hook::Add('issueform::readuservars', array($this, 'readIssueFormFields'));
			Hook::Add('issueform::initdata', array($this, 'initDataIssueFormFields'));
			Hook::Add('issueform::execute', array($this, 'executeIssueFormFields'));
			Hook::add('Schema::get::issue', array($this, 'addToSchema'));
		}
		return $success;
	}

	public function addToSchema($hookName, $args) {
		$schema = $args[0];
		$prop = '{
			"type": "boolean",
			"apiSummary": true,
			"validation": [
				"nullable"
			]
		}';
		$schema->properties->isThemeIssue = json_decode($prop);
	}


	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.themeIssues.displayName');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.themeIssues.description');
	}

	/**
	 * Load the handler to deal with browse by section page requests
	 */
	public function loadPageHandler($hookName, $args) {
		$page = $args[0];
		if ($this->getEnabled() && $page === 'themeissues') {
            define('HANDLER_CLASS', ThemeIssuesHandler::class);
            return Hook::ABORT;
		}
		return Hook::CONTINUE;
	}

	/**
	 * Add fields to the issue editing form
	 */
	public function addIssueFormFields($hookName, $args) {
		$smarty =& $args[1];
		$output =& $args[2];
		$output .= $smarty->fetch($this->getTemplateResource('themeIssuesEdit.tpl'));
		return false;
	}

	/**
	 * Read user input from additional fields in the issue editing form
	 */
	public function readIssueFormFields($hookName, $args) {
		$issueForm =& $args[0];
		$request = $this->getRequest();
		$issueForm->setData('isThemeIssue', $request->getUserVar('isThemeIssue'));
	}

	/**
	 * Save additional fields in the issue editing form
	 */
	public function executeIssueFormFields($hookName, $args) {
		$issueForm = $args[0];
    	$issue = $issueForm->issue;

		// The issueform::execute hook fires twice, once at the start of the
		// method when no issue exists. Only update the object during the
		// second request
		if (!$issue) {
			return;
		}

		$issue->setData('isThemeIssue', (bool) $issueForm->getData('isThemeIssue'));
		Repo::issue()->edit($issue, []);

	}

	/**
	 * Initialize data when form is first loaded
	 */
	public function initDataIssueFormFields($hookName, $args) {
		$issueForm = $args[0];
		$issueForm->setData('isThemeIssue', $issueForm->issue->getData('isThemeIssue'));
	}

	/**
	 * Add section settings to IssueDAO
	 */
	public function addIssueDAOFieldNames($hookName, $args) {
		$fields =& $args[1];
		$fields[] = 'isThemeIssue';
	}

}
?>
