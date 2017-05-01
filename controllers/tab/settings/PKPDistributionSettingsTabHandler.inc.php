<?php

/**
 * @file controllers/tab/settings/PKPDistributionSettingsTabHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPDistributionSettingsTabHandler
 * @ingroup controllers_tab_settings
 *
 * @brief Handle AJAX operations for tabs on Distribution Process page.
 */

// Import the base Handler.
import('lib.pkp.controllers.tab.settings.ManagerSettingsTabHandler');

class PKPDistributionSettingsTabHandler extends ManagerSettingsTabHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
		// In addition to the operations permitted by the parent
		// class, allow Payment AJAX extras.
		$this->addRoleAssignment(
			ROLE_ID_MANAGER,
			array('getPaymentMethods', 'getPaymentFormContents', 'resetPermissions')
		);
		$this->setPageTabs(array(
			'indexing' => 'lib.pkp.controllers.tab.settings.contextIndexing.form.ContextIndexingForm',
			'paymentMethod' => 'lib.pkp.controllers.tab.settings.paymentMethod.form.PaymentMethodForm',
		));
	}

	/**
	 * List payment method options.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON response.
	 */
	function getPaymentMethods($args, $request) {
		return new JSONMessage(true, array_merge(
			array(__('manager.paymentMethod.none')),
			array_map(function($name) {
				return Omnipay\Omnipay::create($name)->getName();
			}, Omnipay\Omnipay::find())
		));
	}

	/**
	 * Get the form contents for the given payment method.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON response.
	 */
	function getPaymentFormContents($args, $request) {
		$paymentPluginName = $request->getUserVar('paymentPluginName');
		$plugins =& PluginRegistry::loadCategory('paymethod');
		if (!isset($plugins[$paymentPluginName])) {
			// Invalid plugin name
			return new JSONMessage(false);
		} else {
			// Fetch and return the JSON-encoded form contents
			$plugin =& $plugins[$paymentPluginName];
			$params = array(); // Blank -- OJS compatibility. Need to supply by reference.
			$templateMgr = TemplateManager::getManager($request);

			// Expose current settings to the template
			$context = $request->getContext();
			foreach ($plugin->getSettingsFormFieldNames() as $fieldName) {
				$templateMgr->assign($fieldName, $plugin->getSetting($context->getId(), $fieldName));
			}

			return new JSONMessage(true, $plugin->displayPaymentSettingsForm($params, $templateMgr));
		}
	}

	/**
	 * Reset permissions data assigned to existing submissions.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON response.
	 */
	function resetPermissions($args, $request) {
		$context = $request->getContext();
		$submissionDao = Application::getSubmissionDAO();
		$submissionDao->deletePermissions($context->getId());

		$notificationManager = new NotificationManager();
		$user = $request->getUser();
		$notificationManager->createTrivialNotification($user->getId());

		return new JSONMessage(true);
	}
}

?>
