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
use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

define('TYPO3_DLOG', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_DLOG']);

/**
 * plugin 'Minicart' for the 'wt_cart' extension.
 *
 * @author  wt_cart Development Team <info@wt-cart.com>
 * @package TYPO3
 * @subpackage  tx_wtcart
 * @version 1.4.0
 */
class tx_wtcart_pi3 extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{

	// make configurations
	public $prefixId = 'tx_wtcart_pi3';
	public $scriptRelPath = 'pi3/class.tx_wtcart_pi3.php';
	public $extKey = 'wt_cart';
	public $tmpl = array();
	public $minicartMarkerArray = array();

	/**
	 * the main method of the PlugIn
	 *
	 * @param string    $content: The PlugIn content
	 * @param array   $conf: The PlugIn configuration
	 * @return  The content that is displayed on the website
	 */
	public function main($content, $conf)
	{
		// make configurations
		$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_wtcart_pi1.'];
		$this->conf = array_merge((array) $this->conf, (array) $conf);

		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;

		// create new instance for function
		$this->div = GeneralUtility::makeInstance('tx_wtcart_div');
		$this->render = GeneralUtility::makeInstance('Tx_WtCart_Utility_Renderer');
		$this->dynamicMarkers = GeneralUtility::makeInstance('tx_wtcart_dynamicmarkers', $this->scriptRelPath);


		$this->tmpl['minicart'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['main.']['template']), '###WTCART_MINICART###'); // Load FORM HTML Template
		$this->tmpl['minicart_empty'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['main.']['template']), '###WTCART_MINICART_EMPTY###'); // Load FORM HTML Template
		//Read Flexform
		$row = $this->pi_getRecord('tt_content', $this->cObj->data['uid']);
		$flexformData = GeneralUtility::xml2array($row['pi_flexform']);
		$pid = $this->pi_getFFvalue($flexformData, 'pid', 'sDEF');

		// cyz: Michael Stein, get pid from typoscript
		$pid = $this->conf['main.']['pid'];

		$session = $GLOBALS['TSFE']->fe_user->getKey('ses', 'wt_cart_' . $pid);

		if (empty($session)) {
			return '';
		}

		$cart = unserialize($session);
		if (!$cart) {
			$cart = new Tx_WtCart_Domain_Model_Cart();
		}

		if ($cart->getCount()) {
			$this->render->renderMiniCart($cart, $this);

			$typolink_conf = array();

			$this->minicartMarkerArray['###MINICART_LINK###'] = $this->pi_linkToPage($this->pi_getLL('to_sample_box'), $pid, "", $typolink_conf);
			$this->minicartMarkerArray['###MINICART_LINK_URL###'] = $this->pi_getPageLink($pid, "", $typolink_conf);
			// Get html template
			$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl['minicart'], $this->minicartMarkerArray);
		} else {
			// Get html template
			$this->content = $this->cObj->substituteMarkerArrayCached($this->tmpl['minicart_empty'], null, $this->minicartMarkerArray);
		}

		// Fill dynamic locallang or typoscript markers
		$this->content = $this->dynamicMarkers->main($this->content, $this);
		// Finally clear not filled markers
		$this->content = preg_replace('|###.*?###|i', '', $this->content);
		return $this->pi_wrapInBaseClass($this->content);
	}

	/**
	 * cyz: Michael Stein: get additional Languages from cyz_wtcart_ext
	 */
	public function pi_loadLL()
	{
		parent::pi_loadLL();

		if (!$this->additional_locallang_include) {
			$basePath = ExtensionManagementUtility::extPath(cyz_wtcart_ext) . 'Resources/Private/Language/locallang.xml';
			$tempLOCAL_LANG = GeneralUtility::readLLfile($basePath, $this->LLkey);
			//array_merge with new array first, so a value in locallang (or typoscript) can overwrite values from ../locallang_db
			$this->LOCAL_LANG = array_merge_recursive($tempLOCAL_LANG, is_array($this->LOCAL_LANG) ? $this->LOCAL_LANG : array());
			if ($this->altLLkey) {
				$tempLOCAL_LANG = GeneralUtility::readLLfile($basePath, $this->altLLkey);
				$this->LOCAL_LANG = array_merge_recursive($tempLOCAL_LANG, is_array($this->LOCAL_LANG) ? $this->LOCAL_LANG : array());
			}
			$this->additional_locallang_include = true;
		}
	}
}
