<?php
/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Ephraim Härer <ephraim.haerer@renolit.com>, RENOLIT SE
 *  (c) 2011-2014 - wt_cart Development Team <info@wt-cart.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */
use \TYPO3\CMS\Core\Utility\GeneralUtility;

define('TYPO3_DLOG', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_DLOG']);

/**
 * Plugin 'Cart' for the 'wt_cart' extension.
 *
 * @author	wt_cart Development Team <info@wt-cart.com>
 * @package	TYPO3
 * @subpackage	tx_wtcart_powermail
 */
class Tx_WtCart_Hooks_Forms16 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{

	/**
	 * Don't show powermail form if session is empty
	 *
	 * @param	string			$content: html content from powermail
	 * @param	array			$piVars: piVars from powermail
	 * @param	object			$pObj: piVars from powermail
	 * @return	bool
	 */
	public function PM_MainContentAfterHook($content, $piVars, &$pObj)
	{
		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.'];
		$piVars = GeneralUtility::_GP('tx_powermail_pi1');

		if ($piVars['mailID'] > 0 || $piVars['sendNow'] > 0) {
			return FALSE; // stop
		}

		if ($conf['powermailContent.']['uid'] > 0 && intval($conf['powermailContent.']['uid']) == $pObj->cObj->data['uid']) { // if powermail uid isset and fits to current CE
			// get products from session
			$cart = unserialize($GLOBALS['TSFE']->fe_user->getKey('ses', 'wt_cart_' . $conf['main.']['pid']));

			// if there are no products in the session clear the content
			if ((!$cart) || $cart->getCount() == 0) {
				$pObj->content = '';
			}
		}
	}

	/**
	 * @param $error
	 * @param $markerArray
	 * @param $innerMarkerArray
	 * @param $sessionfields
	 * @param $obj
	 */
	public function PM_MandatoryHook($error, &$markerArray, &$innerMarkerArray, &$sessionfields, $obj)
	{
		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.'];

		// read cart from session
		$cart = unserialize($GLOBALS['TSFE']->fe_user->getKey('ses', 'wt_cart_' . $conf['main.']['pid']));

		if ($cart) {
			if (!$cart->getOrderNumber()) {
				$registry = GeneralUtility::makeInstance('TYPO3\CMS\Core\Registry');
				$orderNumber = $registry->get('tx_wtcart', 'lastOrder_' . $conf['main.']['pid']);
				if ($orderNumber) {
					$orderNumber += 1;
					$registry = GeneralUtility::makeInstance('TYPO3\CMS\Core\Registry');
					$registry->set('tx_wtcart', 'lastOrder_' . $conf['main.']['pid'], $orderNumber);
				} else {
					$orderNumber = 1;
					$registry = GeneralUtility::makeInstance('TYPO3\CMS\Core\Registry');
					$registry->set('tx_wtcart', 'lastOrder_' . $conf['main.']['pid'], $orderNumber);
				}

				$orderNumberConf = $conf['settings.']['fields.'];
				$this->cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
				$this->cObj->start(array('ordernumber' => $orderNumber), $orderNumberConf['ordernumber']);
				$orderNumber = $this->cObj->cObjGetSingle($orderNumberConf['ordernumber'], $orderNumberConf['ordernumber.']);

				$cart->setOrderNumber($orderNumber);
			}

			if (TYPO3_DLOG) {
				GeneralUtility::devLog('ordernumber', 'wt_cart', 0, array($cart->getOrderNumber()));
			}
		}

		$GLOBALS['TSFE']->fe_user->setKey('ses', 'wt_cart_' . $conf['main.']['pid'], serialize($cart));
		$GLOBALS['TSFE']->storeSessionData();
	}

	public function PM_SubmitEmailHook($subpart, &$maildata, &$sessiondata, &$markerArray, $obj)
	{
		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.']['settings.']['overall.'];
		$orderNumber = $GLOBALS['TSFE']->cObj->cObjGetSingle($conf['ordernumber'], $conf['ordernumber.']);

		$maildata['subject'] = str_replace('###ORDERNUMBER###', $orderNumber, $maildata['subject']);
	}

	/**
	 * Clear cart after submit
	 *
	 * @param	string			$content: html content from powermail
	 * @param	array			$conf: TypoScript from powermail
	 * @param	array			$session: Values in session
	 * @param	boolean			$ok: if captcha not failed
	 * @param	object			$pObj: Parent object
	 * @return	void
	 */
	public function PM_SubmitLastOneHook($content, $conf, $session, $ok, $pObj)
	{
		$piVars = GeneralUtility::_GPmerged('tx_powermail_pi1');
		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.'];

		if ($piVars['mailID'] == $conf['powermailContent.']['uid']) {
			/** @var Tx_WtCart_Utility_Cart $utilityCart */
			$utilityCart = GeneralUtility::makeInstance('Tx_WtCart_Utility_Cart');
			$utilityCart->removeAllProductsFromSession();
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_cart/lib/class.tx_wtcart_powermail.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_cart/lib/class.tx_wtcart_powermail.php']);
}
