<?php

/**
 * @file plugins/generic/reviewerInterests/ReviewerInterestsPlugin.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerInterestsPlugin
 * @ingroup plugins_generic_reviewerInterests
 *
 * @brief Reviewer Interests plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class ReviewerInterestsPlugin extends GenericPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return true;
		if ($success && $this->getEnabled()) {
			// Replace interests block on user profile with dropdown containing constrained list.
			HookRegistry::register('TemplateManager::display', array($this, 'insertInterests'));
		}
		return $success;
	}

	function getDisplayName() {
		return __('plugins.generic.reviewerInterests.displayName');
	}

	function getDescription() {
		return __('plugins.generic.reviewerInterests.description');
	}

	/**
	 * Extend the {url ...} smarty to support this plugin.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['id'])) {
			$params['path'] = array_merge($params['path'], array($params['id']));
			unset($params['id']);
		}
		return $smarty->smartyUrl($params, $smarty);
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			)
		);
		if ($isSubclass) $pageCrumbs[] = array(
			Request::url(null, 'manager', 'plugins'),
			'manager.plugins'
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.reviewerInterests.manager.settings'));
		}
		return parent::getManagementVerbs($verbs);
	}

	/**
	 * Insert Google Scholar account info into author submission step 3
	 */
	function insertInterests($hookName, $params) {

		if ($this->getEnabled()) {
			$smarty =& $params[0];
			$templateName =& $params[1];

			if ($templateName == 'user/profile.tpl') {
				// fetch the original template.
				$contents = $smarty->fetch($templateName);
				$contents = preg_replace('|<td class="label">\s*(<label for="interests"\s*>.*?</label>)\s*</td>\s*<td\s+.*?</td>|s',
						'<td class="label">$1</td><td>'. $this->getReviewerSelect() . '</td>', $contents);

				$params[4] = $contents;
				return true;
			}
		}
		return false;
	}

	/**
	 * Execute a management verb on this plugin
	 * @param $verb string
	 * @param $args array
	 * @param $message string Result status message
	 * @param $messageParams array Parameters for the message key
	 * @return boolean
	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;

		switch ($verb) {
			case 'settings':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
				$journal =& Request::getJournal();

				$this->import('ReviewerInterestsSettingsForm');
				$form = new ReviewerInterestsSettingsForm($this, $journal->getId());
				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, 'manager', 'plugin');
						return false;
					} else {
						$this->setBreadCrumbs(true);
						$form->display();
					}
				} else {
					$this->setBreadCrumbs(true);
					$form->initData();
					$form->display();
				}
				return true;
			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}

	/**
	 * Return a string containing the HTML for the selector for choosing
	 * reviewer interests.
	 * @return string
	 */
	function getReviewerSelect() {
		$html = '';

		return $html;
	}
}
?>