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

			if ($templateName == 'user/profile.tpl' || $templateName == 'user/register.tpl' || $templateName == 'sectionEditor/selectReviewer.tpl' || $templateName == 'sectionEditor/createReviewerForm.tpl') {
				// fetch the original template.
				$contents = $smarty->fetch($templateName);
				if ($templateName == 'user/profile.tpl') {
				$contents = preg_replace('|<td class="label">\s*(<label for="interests"\s*>.*?</label>)\s*</td>\s*<td\s+.*?</td>|s',
						'<td class="label">$1</td><td>'. $this->getReviewerSelect() . '</td>', $contents);
				} else if ($templateName == 'sectionEditor/createReviewerForm.tpl') {
					$contents = preg_replace('|<td class="value">\s*<script\s.*?>.*?</script>\s*<div id="interests">.*?</div>\s*</td>|s',
						'<td class="value">'. $this->getReviewerSelect() . '</td>', $contents);
				} else if ($templateName == 'user/register.tpl') {
					$contents = preg_replace('|<div id="reviewerInterestsContainer".*?>\s*(<label class="desc">.*?</label>).*?</div>\s*</td>|s',
						'$1<br /><div style="margin: 5px;">' . $this->getReviewerSelect() . '</div></td>' , $contents);
				} else {

					// First, remove the option for interests if it is there.
					$contents = preg_replace('|<option\s+[^>]*\s+value="interests"[^>]*>.*?</option>|s', '', $contents);

					// Second, add the new field for reviewer interests.
					$formTag = '';
					$submitButton = '';
					if (preg_match('|(<form.+?selectReviewer.*?>)|', $contents, $matches)) {
						$formTag = $matches[1];
					}

					if (preg_match('|(<input.+?type="submit".*?>)|', $contents, $matches)) {
						$submitButton = $matches[1];
					}

					// if they submitted a search on the interests field, fix up the text search field and set the select dropdown.
					if (Request::getUserVar('searchField') == 'interests') {
						$contents = preg_replace('|(<input type="text" size="10" name="search" class="textField" value=").*?(" />)|', '$1$2', $contents);
					}
					$contents = preg_replace('|</form>|',
						'</form><br />' . __('search.operator.or') . $formTag .
						'<input type="hidden" name="searchField" value="interests"/>' .
						'<input type="hidden" name="searchMatch" value="is"/>' .
						'<p>' . __('user.interests') . ': &nbsp;' . $this->getReviewerSelect('search', false, Request::getUserVar('searchField') == 'interests' ? array(Request::getUserVar('search')) : null) . $submitButton . '</p></form>', $contents);
				}

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
	 * @param $name the field name
	 * @param $isMultiple is this a multi select field.
	 * @param $chosenInterests an array of values to preselect.
	 * @return string
	 */
	function getReviewerSelect($name = "keywords[interests][]", $isMultiple = true, $chosenInterests = null) {

		$html = '<select name="' . $name . '" ';
		$html .= $isMultiple ? 'size="5" multiple="multiple">' : '>';

		$journal =& Request::getJournal();
		$user =& Request::getUser();
		$existingInterests = array();

		if ($user && !$chosenInterests) {
			import('lib.pkp.classes.user.InterestManager');
			$interestManager = new InterestManager();
			$existingInterests = $interestManager->getInterestsForUser($user);
		}

		if (is_array($chosenInterests)) {
			$existingInterests = $chosenInterests;
		}

		$interests = $this->getSetting($journal->getId(), 'reviewerInterests');

		foreach ($interests as $interest) {
			$isSelected = in_array($interest, $existingInterests) ? 'selected="selected"' : '';
			$html .= '<option value="' . htmlentities($interest) . '" ' . $isSelected . '>' . htmlentities($interest) . '</option>';
		}

		$html .= '</select>';

		return $html;
	}
}
?>
