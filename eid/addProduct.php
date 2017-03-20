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

if (!defined('PATH_typo3conf')) {
	die('Could not access this script directly!');
}

define('TYPO3_DLOG', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_DLOG']);

/**
 * Plugin 'Cart' for the 'wt_cart' extension.
 *
 * @author	Daniel Lorenz <daniel.lorenz@extco.de>
 * @package	TYPO3
 * @subpackage	tx_wtcart
 * @version	1.5.0
 */
class addProduct extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{

	// make configurations
	public $prefixId = 'tx_wtcart_eid';
	public $scriptRelPath = 'eid/addProduct.php';
	public $extKey = 'wt_cart';
	public $gpvar = array();
	public $taxes = array();

	public function main()
	{
		// eID specific initialization of user and database
		\TYPO3\CMS\Frontend\Utility\EidUtility::initFeUser();
		\TYPO3\CMS\Frontend\Utility\EidUtility::connectDB();
		$pid = htmlentities(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('cartID'));

		global $TYPO3_CONF_VARS;
		$GLOBALS['TSFE'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController', $TYPO3_CONF_VARS, $pid, 0);
		$GLOBALS['TSFE']->initFEuser();
		$GLOBALS['TSFE']->determineId();
		$GLOBALS['TSFE']->getCompressedTCarray();
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->getConfigArray();

		$this->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
		$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.'];

		// create new instance for function
		$this->div = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_wtcart_div');

		// in this version it is not possible mixing prices for products
		$this->gpvar['isNetPrice'] = intval($this->conf['main.']['isNetCart']) == 0 ? FALSE : TRUE;

		// parse all taxclasses
		$this->taxes = $this->div->parseTaxes($this);

		/* Cart - Section */

		$cartID = $pid;

		// read cart from session
		$cart = unserialize($GLOBALS['TSFE']->fe_user->getKey('ses', 'wt_cart_' . $cartID));
		if (!$cart) {

			$this->isNetCart = intval($this->conf['main.']['isNetCart']) == 0 ? FALSE : TRUE;

			$cart = new Tx_WtCart_Domain_Model_Cart($this->isNetCart);

			// preset shipping for new cart
			if (!$cart->getShipping()) {
				// parse all shippings
				$shippings = $this->div->parseServices('Shipping', $this);
				$cart->setShipping($shippings[$this->conf['shipping.']['preset']]);
			}

			// preset payment for new cart
			if (!$cart->getPayment()) {
				// parse all payments
				$payments = $this->div->parseServices('Payment', $this);
				$cart->setPayment($payments[$this->conf['payment.']['preset']]);
			}
		}

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wt_cart']['changeCartAfterLoad']) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wt_cart']['changeCartAfterLoad'] as $funcRef) {
				if ($funcRef) {
					$params = array(
						'cart' => &$cart
					);

					\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
				}
			}
		}

		//read variables
		$this->div->getGPVars($this);

		// in this version it is not possible mixing prices for products
		$this->gpvar['isNetPrice'] = $cart->getIsNetCart();

		if (TYPO3_DLOG) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('pivars', $this->extKey, 0, $this->piVars);
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('gpvars', $this->extKey, 0, $this->gpvar);
		}

		$count = 0;
		if ($this->gpvar['multi']) {
			foreach ($this->gpvar['multi'] as $single) {
				$this->gpvar = $single;
				$this->gpvar['isNetPrice'] = $cart->getIsNetCart();
				$count += $this->parseDataToProductToCart($cart);
			}
		} else {
			$count += $this->parseDataToProductToCart($cart);
		}

		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wt_cart']['changeProductBeforeAddToCart']) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wt_cart']['changeProductBeforeAddToCart'] as $funcRef) {
				if ($funcRef) {
					$params = array(
						'newProduct' => &$newProduct
					);

					\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
				}
			}
		}

		$cart->debug();

		// save cart to session
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'wt_cart_' . $cartID, serialize($cart));
		$GLOBALS['TSFE']->storeSessionData();

		if ($count) {
			echo("OK");
		} else {
			echo("Error.");
		}
	}

	private function parseDataToProductToCart(&$cart)
	{
		// if content id (cid) is given, then product added from plugin
		if ($this->gpvar['cid']) {
			// parse data from flexform
			$this->parseDataFromFlexform();
		} elseif ($this->gpvar['puid']) {
			// product added by own form
			if (!$this->gpvar['ownForm']) {
				$this->div->getProductDetails($this->gpvar, $this);
			} else {
				$this->parseDataFromOwnForm();
			}
		}

		// if no qty given set qty to 1
		if ($this->gpvar['qty'] === 0) {
			$this->gpvar['qty'] = 1;
		}

		// create new product

		if ($this->gpvar['puid']) {
			$newProduct = $this->div->createProduct($this);

			$newProduct->setServiceAttribute1($this->gpvar['service_attribute_1']);
			$newProduct->setServiceAttribute2($this->gpvar['service_attribute_2']);
			$newProduct->setServiceAttribute3($this->gpvar['service_attribute_3']);

			$newProduct->setAdditionalArray($this->gpvar['additional']);

			if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wt_cart']['changeProductBeforeAddToCart']) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wt_cart']['changeProductBeforeAddToCart'] as $funcRef) {
					if ($funcRef) {
						$params = array(
							'newProduct' => &$newProduct
						);

						\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
					}
				}
			}

			$cart->addProduct($newProduct);
		} else {
			return 0;
		}

		return 1;
	}
}

$output = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('addProduct');
$output->main();
