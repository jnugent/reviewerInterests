<?php

/**
 * @file plugins/generic/reviewerInterests/ReviewerInterestsSettingsForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerInterestsSettingsForm
 * @ingroup plugins_generic_reviewerInterests
 *
 * @brief Form for journal managers to modify Reviewer Interests plugin settings
 */


import('lib.pkp.classes.form.Form');

class ReviewerInterestsSettingsForm extends Form {

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function ReviewerInterestsSettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'templates/settingsForm.tpl');
		$this->addCheck(new FormValidatorCustom($this, 'reviewerInterests', 'required', 'plugins.generic.reviewerAnalytics.manager.settings.reviewerInterestsRequired', create_function('$keywords,$form', '$keywords = $form->getData(\'keywords\'); return count($keywords[\'interests\']) > 0;'), array(&$this)));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$this->_data = array(
			'interestsKeywords' => $plugin->getSetting($journalId, 'reviewerInterests'),
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('keywords'));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;
		$keywords = $this->getData('keywords');
		$interests = $keywords['interests'];

		$plugin->updateSetting($journalId, 'reviewerInterests', $interests, 'object');
	}
}

?>
