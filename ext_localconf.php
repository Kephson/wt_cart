<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'pi1/class.tx_wtcart_pi1.php', '_pi1', 'list_type', 0);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'pi2/class.tx_wtcart_pi2.php', '_pi2', 'list_type', 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'pi3/class.tx_wtcart_pi3.php', '_pi3', 'list_type', 0);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['tx_wtcart_evalprice'] = 'EXT:wt_cart/pi2/class.tx_wtcart_evalprice.php';

$TYPO3_CONF_VARS['FE']['eID_include']['addProduct'] = 'EXT:wt_cart/eid/addProduct.php';

// powermail hooks and signal slots

$version16 = version_compare(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('powermail'), '1.6.0');
$version20 = version_compare(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('powermail'), '2.0.0');
$version21 = version_compare(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('powermail'), '2.1.0');
$version22 = version_compare(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('powermail'), '2.2.0');

if (( $version16 >= 0 ) && ( $version20 < 0 )) {
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['powermail']['PM_MainContentHookAfter'][] = 'EXT:wt_cart/Classes/Hooks/Tx_WtCart_Hooks_Forms16.php:tx_wtcart_powermail';
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['powermail']['PM_SubmitEmailHook'][] = 'EXT:wt_cart/Classes/Hooks/Tx_WtCart_Hooks_Forms16.php:tx_wtcart_powermail';
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['powermail']['PM_MandatoryHook'][] = 'EXT:wt_cart/Classes/Hooks/Tx_WtCart_Hooks_Forms16.php:tx_wtcart_powermail';
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['powermail']['PM_SubmitLastOne'][] = 'EXT:wt_cart/Classes/Hooks/Tx_WtCart_Hooks_Forms16.php:tx_wtcart_powermail';
}

if (( $version20 >= 0 ) && ( $version21 < 0 )) {
	$pmForms = 'Tx_Powermail_Controller_FormsController';
	$wtForms = 'Tx_WtCart_Hooks_Forms20';

	$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
	$signalSlotDispatcher->connect(
		$pmForms, 'formActionBeforeRenderView', $wtForms, 'checkTemplate'
	);
	$signalSlotDispatcher->connect(
		$pmForms, 'createActionBeforeRenderView', $wtForms, 'slotCreateActionBeforeRenderView'
	);
	$signalSlotDispatcher->connect(
		$pmForms, 'createActionAfterSubmitView', $wtForms, 'clearSession'
	);
}

// cyz: Michael Stein: disable version-checking. We are old enough
$pmForms = 'In2code\Powermail\Controller\FormController';
$wtForms = 'Tx_WtCart_Hooks_Forms21';

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
$signalSlotDispatcher->connect(
	$pmForms, 'formActionBeforeRenderView', $wtForms, 'checkTemplate'
);
$signalSlotDispatcher->connect(
	$pmForms, 'createActionBeforeRenderView', $wtForms, 'slotCreateActionBeforeRenderView'
);
$signalSlotDispatcher->connect(
	$pmForms, 'createActionAfterSubmitView', $wtForms, 'clearSession'
);
