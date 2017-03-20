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
 * @subpackage	tx_wtcart
 * @version	1.2.2
 */
class tx_wtcart_div extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{

	// make configurations
	public $prefixId = 'tx_wtcart_pi1';
	public $scriptRelPath = 'pi1/class.tx_wtcart_pi1.php';
	public $extKey = 'wt_cart';

	/**
	 * read product details (title, price from table)
	 *
	 * @param array   $gpvar: array with product uid, title, taxclass, etc...
	 * @param $pObj
	 * @internal param array $pobj : Parent Object
	 * @return array $arr: array with title and price
	 */
	public function getProductDetails(&$gpvar, &$pObj)
	{
		// all values already filled via POST or GET param
		if (!empty($gpvar['title']) && !empty($gpvar['price']) && !empty($gpvar['taxclass'])) {
			return $gpvar;
		}

		$puid = intval($gpvar['puid']);
		// stop if no puid given
		if ($puid === 0) {
			return FALSE;
		}
		/** @var \TYPO3\CMS\Core\Resource\FileRepository $fileRepository */
		$fileRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		$fileObject = $fileRepository->findFileReferenceByUid($puid);



		if ($fileObject) {
			$gpvar['title'] = $fileObject->getProperty($pObj->conf['db.']['title']) ? $fileObject->getProperty($pObj->conf['db.']['title']) : 'dummy';
			$gpvar['price'] = $fileObject->getProperty($pObj->conf['db.']['price']);
			$gpvar['taxclass'] = $fileObject->getProperty($pObj->conf['db.']['taxclass']);

			if ($row[$pObj->conf['db.']['sku']]) {
				
			}
			$gpvar['sku'] = 100;
			if ($row[$pObj->conf['db.']['service_attribute_1']]) {
				$gpvar['service_attribute_1'] = $fileObject->getProperty($pObj->conf['db.']['service_attribute_1']);
			}
			if ($row[$pObj->conf['db.']['service_attribute_2']]) {
				$gpvar['service_attribute_2'] = $fileObject->getProperty($pObj->conf['db.']['service_attribute_2']);
			}
			if ($row[$pObj->conf['db.']['service_attribute_3']]) {
				$gpvar['service_attribute_3'] = $fileObject->getProperty($pObj->conf['db.']['service_attribute_3']);
			}
			if ($row[$pObj->conf['db.']['has_fe_variants']]) {
				$gpvar['has_fe_variants'] = $fileObject->getProperty($pObj->conf['db.']['has_fe_variants']);
			}

			if ($pObj->conf['db.']['additional.']) {
				$gpvar['additional'] = array();
				foreach ($pObj->conf['db.']['additional.'] as $additionalKey => $additionalValue) {
					if ($additionalValue['field']) {
						$gpvar['additional'][rtrim($additionalKey, '.')] = $fileObject->getProperty($additionalValue['field']);
					} elseif ($additionalValue['value']) {
						$gpvar['additional'][rtrim($additionalKey, '.')] = $additionalValue['value'];
					}
				}
			}
		} else {
			GeneralUtility::devLog('ERROR file not found', $puid, 0, $out);
		}
	}

	public function getVariantDetails(&$variant, &$conf)
	{
		$variantId = $variant->getId();

		$table = $conf['db.']['table'];
		$l10nParent = $conf['db.']['l10n_parent'] ? $conf['db.']['l10n_parent'] : 'l10n_parent';

		$select = $table . '.' . $conf['db.']['title'];

		if ($conf['db.']['price_calc_method'] != '' &&
			$conf['db.']['price_calc_method'] != '{$plugin.wtcart.db.variants.db.price_calc_method}') {
			$select .= ', ' . $table . '.' . $conf['db.']['price_calc_method'];
		}
		if ($conf['db.']['price'] != '' &&
			$conf['db.']['price'] != '{$plugin.wtcart.db.variants.db.price}') {
			$select .= ', ' . $table . '.' . $conf['db.']['price'];
		}
		if ($conf['db.']['inherit_price'] != '' &&
			$conf['db.']['variants.']['db.']['price'] != '{$plugin.wtcart.db.variants.db.inherit_price}') {
			$select .= ', ' . $table . '.' . $conf['db.']['inherit_price'];
		}
		if ($conf['db.']['sku'] != '' &&
			$conf['db.']['sku'] != '{$plugin.wtcart.db.variants.db.sku}') {
			$select .= ', ' . $table . '.' . $conf['db.']['sku'];
		}
		if ($conf['db.']['has_fe_variants'] != '' &&
			$conf['db.']['has_fe_variants'] != '{$plugin.wtcart.db.variants.db.has_fe_variants}') {
			$select .= ', ' . $table . '.' . $conf['db.']['has_fe_variants'];
		}

		if ($conf['db.']['additional.']) {
			foreach ($conf['db.']['additional.'] as $additional) {
				if ($additional['field']) {
					$select .= ', ' . $table . '.' . $additional['field'];
				}
			}
		}

		$where = ' ( ' . $table . '.uid = ' . $variantId . ' OR ' . $l10nParent . ' = ' . $variantId . ' )' .
			' AND sys_language_uid = ' . $GLOBALS['TSFE']->sys_language_uid;
		$where .= $this->cObj->enableFields($table);
		$groupBy = '';
		$orderBy = '';
		$limit = 1;

		if (TYPO3_DLOG) {
			$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = TRUE;
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select, $table, $where, $groupBy, $orderBy, $limit);

		if (TYPO3_DLOG) {
			$out = array(
				'select' => $select,
				'table' => $table,
				'where' => $where,
				'groupBy' => $groupBy,
				'orderBy' => $orderBy,
				'limit' => $limit,
				'query' => $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery
			);
			GeneralUtility::devLog('query variant', $this->extKey, 0, $out);
		}

		if ($res) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

			$variant->setTitle($row[$conf['db.']['title']]);
			if ($row[$conf['db.']['sku']]) {
				$variant->setSku($row[$conf['db.']['sku']]);
			}


			if ($row[$conf['db.']['price_calc_method']]) {
				$variant->setPriceCalcMethod($row[$conf['db.']['price_calc_method']]);
			} else {
				$variant->setPriceCalcMethod(0);
			}

			// if inherit_price is defined then check the inherit_price and replace the with variant price
			// if inherit_price is not defined then replace the with variant price
			if ($conf['db.']['inherit_price'] != '' && $conf['db.']['price'] != '{$plugin.wtcart.db.variants.db.inherit_price}') {
				if ($row[$conf['db.']['inherit_price']]) {
					if ($row[$conf['db.']['price']]) {
						$variant->setPrice($row[$conf['db.']['price']]);
					}
				}
			} else {
				if ($row[$conf['db.']['price']]) {
					$variant->setPrice($row[$conf['db.']['price']]);
				}
			}

			if ($row[$conf['db.']['has_fe_variants']]) {
				$variant->setHasFeVariants($row[$conf['db.']['has_fe_variants']]);
			}

			if ($conf['db.']['additional.']) {
				foreach ($conf['db.']['additional.'] as $additionalKey => $additionalValue) {
					if ($additionalValue['field']) {
						$variant->setAdditional(rtrim($additionalKey, '.'), $row[$additionalValue['field']]);
					} elseif ($additionalValue['value']) {
						$variant->setAdditional(rtrim($additionalKey, '.'), $additionalValue['value']);
					}
				}
			}
		} else {
			$out = array(
				'select' => $select,
				'table' => $table,
				'where' => $where,
				'groupBy' => $groupBy,
				'orderBy' => $orderBy,
				'limit' => $limit,
				'query' => $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery
			);

			GeneralUtility::devLog('ERROR in query variant', $this->extKey, 0, $out);
		}
	}

	/**
	 * returns message with optical flair
	 *
	 * @param string   $str: Message to show
	 * @param int      $pos: Is this a positive message? (0,1,2)
	 * @param bool|int $die : Process should be died now
	 * @param bool|int $prefix : Activate or deactivate prefix "$extKey: "
	 * @param string   $id: id to add to the message (maybe to do some js effects)
	 * @return string  $string: Manipulated string
	 */
	public function msg($str, $pos = 0, $die = 0, $prefix = 1, $id = '')
	{
		// config
		if ($prefix) {
			$string = $this->extKey . ($pos != 1 && $pos != 2 ? ' Error' : '') . ': ';
		}
		$string .= $str;
		// URLprefix with domain
		$URLprefix = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . '/';
		// if request_host is different to site_url (TYPO3 runs in a subfolder)
		if (GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . '/' != GeneralUtility::getIndpEnv('TYPO3_SITE_URL')) {
			$URLprefix .= str_replace(GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . '/', '', GeneralUtility::getIndpEnv('TYPO3_SITE_URL')); // add folder (like "subfolder/")
		}

		// let's go
		switch ($pos) {
			default: // error
				$wrap = '<div class="' . $this->extKey . '_msg_error" style="background-color: #FBB19B; background-position: 4px 4px; background-image: url(' . $URLprefix . 'typo3/gfx/error.png); background-repeat: no-repeat; padding: 5px 30px; font-weight: bold; border: 1px solid #DC4C42; margin-bottom: 20px; font-family: arial, verdana; color: #444; font-size: 12px;"';
				// add css id
				if ($id)
					$wrap .= ' id="' . $id . '"';
				$wrap .= '>';
				break;
			case 1: // success
				$wrap = '<div class="' . $this->extKey . '_msg_status" style="background-color: #CDEACA; background-position: 4px 4px; background-image: url(' . $URLprefix . 'typo3/gfx/ok.png); background-repeat: no-repeat; padding: 5px 30px; font-weight: bold; border: 1px solid #58B548; margin-bottom: 20px; font-family: arial, verdana; color: #444; font-size: 12px;"';
				// add css id
				if ($id)
					$wrap .= ' id="' . $id . '"';
				$wrap .= '>';
				break;
			case 2: // note
				$wrap = '<div class="' . $this->extKey . '_msg_error" style="background-color: #DDEEF9; background-position: 4px 4px; background-image: url(' . $URLprefix . 'typo3/gfx/information.png); background-repeat: no-repeat; padding: 5px 30px; font-weight: bold; border: 1px solid #8AAFC4; margin-bottom: 20px; font-family: arial, verdana; color: #444; font-size: 12px;"';
				// add css id
				if ($id)
					$wrap .= ' id="' . $id . '"';
				$wrap .= '>';
				break;
		}

		// return message if $die is false
		if (!$die) {
			return $wrap . $string . '</div>';
		} else {
			die($string);
		}
	}

	/**
	 * add_variant_gpvar_to_imagelinkwrap():  Adds all table.field of the variant to
	 *                                          imageLinkWrap.typolink.additionalParams.wrap
	 *
	 * @param array   $product: array with product uid, title, taxclass, etc...
	 * @param string   $tsKey: key of the current TypoScript configuration array
	 * @param array   $tsConf: the current TypoScript configuration array
	 * @param $pObj
	 * @internal param array $pobj : Parent Object
	 * @return array $tsConf: configuration array added with the varaition gpvars
	 * @version 1.2.2
	 * @since 1.2.2
	 */
	public function add_variant_gpvar_to_imagelinkwrap($product, $tsKey, $tsConf, $pObj)
	{
		// return there isn't any variant
		if (!is_array($pObj->conf['settings.']['variants.'])) {
			return $tsConf;
		}

		// get all variant key/value pairs from the current product
		$array_add_gpvar = $this->get_variant_from_product($product, $pObj);

		// add variant key/value pairs to imageLinkWrap
		foreach ((array) $array_add_gpvar as $key => $value) {
			$strWrap = $tsConf['imageLinkWrap.']['typolink.']['additionalParams.']['wrap'];
			$strWrap = $strWrap . '&' . $this->prefixId . '[' . $key . ']=' . $value;
			$tsConf['imageLinkWrap.']['typolink.']['additionalParams.']['wrap'] = $strWrap;
		}

		return $tsConf;
	}

	/**
	 * add_qty_marker():  Allocates to the global markerArray a value for ###QTY_NAME###
	 *                          in case of variant
	 *                          It returns in aray with hidden fields like
	 *                          <input type="hidden"
	 *                                 name="tx_wtcart_pi1[puid][20][]"
	 *                                 value="tx_wtcart_pi1[tx_org_calentrance.uid]=4|tx_wtcart_pi1[qty]=91" />
	 *
	 * @param $product
	 * @param array   $markerArray: current marker array
	 * @param $pObj
	 * @internal param array $products : array with products with elements uid, title, taxclass, etc...
	 * @internal param array $pobj : Parent Object
	 * @return array $markerArray: with added element ###VARIANTS### in case of variants
	 * @version 1.2.2
	 * @since 1.2.2
	 */
	public function add_qtyname_marker($product, $markerArray, $pObj)
	{
		// default name for QTY. It is compatible with version 1.2.1
		$markerArray['###QTY_NAME###'] = 'tx_wtcart_pi1[qty][' . $product->getTableProductId() . ']';

		// return there isn't any variant
		if (!is_array($pObj->conf['settings.']['variant.'])) {
			return $markerArray;
		}

		$strMarker = NULL;
		// get all variant key/value pairs from the current product
		$array_add_gpvar = $this->get_variant_from_product($product, $pObj);
		$array_add_gpvar['puid'] = $product->getTableProductId();
		// generate the marker array
		foreach ((array) $array_add_gpvar as $key => $value) {
			$strMarker = $strMarker . '[' . $key . '=' . $value . ']';
		}
		$markerArray['###QTY_NAME###'] = 'tx_wtcart_pi1[qty]' . $strMarker;

		return $markerArray;
	}

	/**
	 * get_variant_from_product():  Get an array with the variant values
	 *                                out of the current product
	 *
	 * @param array   $product: array with product uid, title, taxclass, etc...
	 * @param $pObj
	 * @internal param array $pobj : Parent Object
	 * @return array $arrVariants: array with variant key/value pairs
	 * @version 1.2.2
	 * @since 1.2.2
	 */
	private function get_variant_from_product($product, $pObj)
	{
		$arrVariants = NULL;

		// return if there isn't any variant
		if (!is_array($pObj->conf['settings.']['variant.'])) {
			return $arrVariants;
		}

		// loop through ts array variant
		foreach ($pObj->conf['settings.']['variant.'] as $keyVariant) {
			// product contains variant key from ts
			if (in_array($keyVariant, array_keys($product))) {
				$arrVariants[$keyVariant] = $product[$keyVariant];
				if (empty($arrVariants[$keyVariant])) {
					unset($arrVariants[$keyVariant]);
				}
			}
		}

		return $arrVariants;
	}

	/**
	 * get_variant_from_piVars(): Get variant values from piVars
	 *                              variant values have to be content of
	 *                              ts array variant and of piVars
	 *
	 * @param $pObj
	 * @internal param array $product : array with product uid, title, taxclass, etc...
	 * @internal param array $pobj : Parent Object
	 * @return array $arrVariants: array with variant key/value pairs
	 * @version 1.2.2
	 * @since 1.2.2
	 */
	private function get_variant_from_piVars($pObj)
	{
		$arrVariant = NULL;

		// return there isn't any variant
		if (!is_array($pObj->conf['settings.']['variant.'])) {
			return $arrVariant;
		}

		// loop through ts variant array
		foreach ($pObj->conf['settings.']['variant.'] as $key => $tableField) {
			// piVars contain variant key
			if (!empty($pObj->piVars[$tableField])) {
				$arrVariant[$tableField] = mysql_escape_string($pObj->piVars[$tableField]);
			}
		}

		return $arrVariant;
	}

	/**
	 * get_variant_from_qty(): Get variant values out of the name of the qty field
	 *                              variant values have to be content of
	 *                              ts array variant and of qty field
	 *
	 * @param $pObj
	 * @internal param array $product : array with product uid, title, taxclass, etc...
	 * @internal param array $pobj : Parent Object
	 * @return array $arrVariants: array with variant key/value pairs
	 * @version 1.2.2
	 * @since 1.2.2
	 */
	private function get_variant_from_qty($pObj)
	{
		$arrVariant = NULL;

		// return there isn't any variant
		if (!is_array($pObj->conf['settings.']['variant.'])) {
			return $arrVariant;
		}

		$int_counter = 0;
		foreach ($pObj->piVars['qty'] as $key => $value) {
			$arrQty[$int_counter]['qty'][$key] = $value;
			$int_counter++;
		}

		foreach ($arrQty as $key => $piVarsQty) {
			// iterator object
			$data = new RecursiveArrayIterator($piVarsQty['qty']);
			$iterator = new RecursiveIteratorIterator($data, TRUE);
			// top level of ecursive array
			$iterator->rewind();

			// get all variant key/value pairs from qty name
			foreach ($iterator as $keyIterator => $valueIterator) {
				// i.e for a key: tx_org_calentrance.uid=4
				list($keyVariant, $valueVariant) = explode('=', $keyIterator);
				if ($keyVariant == 'puid') {
					$arrVariant[$key]['puid'] = $valueVariant;
				}
				// i.e arr_var[tx_org_calentrance.uid] = 4
				$arr_from_qty[$key][$keyVariant] = $valueVariant;
				if (is_array($valueIterator)) {
					list($keyVariant, $valueVariant) = explode('=', key($valueIterator));
					if ($keyVariant == 'puid') {
						$arrVariant[$key]['puid'] = $valueVariant;
					}
					$arr_from_qty[$key][$keyVariant] = $valueVariant;
				}
				// value is the value of the field qty in every case
				if (!is_array($valueIterator)) {
					$arrVariant[$key]['qty'] = $valueIterator;
				}
			}

			// loop through ts variant array
			foreach ($pObj->conf['settings.']['variant.'] as $keyVariant => $tableField) {
				// piVars contain variant key
				if (!empty($arr_from_qty[$key][$tableField])) {
					$arrVariant[$key][$tableField] = mysql_escape_string($arr_from_qty[$key][$tableField]);
				}
			}
		}

		return $arrVariant;
	}

	/**
	 * _replace_marker_in_sql(): Replace marker in the SQL query
	 *                             MARKERS are
	 *                             - GET/POST markers
	 *                             - enable_field markers
	 *                             SYNTAX is
	 *                             - ###GP:TABLE###
	 *                             - ###GP:TABLE.FIELD###
	 *                             - ###ENABLE_FIELD:TABLE.FIELD###
	 *
	 * @param array   $gpvar: array with product uid, title, taxclass, etc...
	 * @param $pObj
	 * @internal param array $pobj : Parent Object
	 * @return void
	 * @version 1.2.2
	 * @since 1.2.2
	 */
	private function _replace_marker_in_sql(&$gpvar, &$pObj)
	{
		// set marker array with values from GET
		foreach (GeneralUtility::_GET() as $table => $arrFields) {
			if (is_array($arrFields)) {
				foreach ($arrFields as $field => $value) {
					$tableField = strtoupper($table . '.' . $field);
					$marker['###GP:' . strtoupper($tableField) . '###'] = mysql_escape_string($value);
				}
			}
			if (!is_array($arrFields)) {
				$marker['###GP:' . strtoupper($table) . '###'] = mysql_escape_string($arrFields);
			}
		}

		// set and overwrite marker array with values from POST
		foreach (GeneralUtility::_POST() as $table => $arrFields) {
			if (is_array($arrFields)) {
				foreach ($arrFields as $field => $value) {
					$tableField = strtoupper($table . '.' . $field);
					$marker['###GP:' . strtoupper($tableField) . '###'] = mysql_escape_string($value);
				}
			}
			if (!is_array($arrFields)) {
				$marker['###GP:' . strtoupper($table) . '###'] = mysql_escape_string($arrFields);
			}
		}

		// get the SQL query from ts, allow stdWrap
		$query = $pObj->cObj->stdWrap($pObj->conf['db.']['sql'], $pObj->conf['db.']['sql.']);

		// get all gp:marker out of the query
		$arr_gpMarker = array();
		preg_match_all('|###GP\:(.*)###|U', $query, $arrResult, PREG_PATTERN_ORDER);
		if (isset($arrResult[0])) {
			$arr_gpMarker = $arrResult[0];
		}

		// get all enable_fields:marker out of the query
		$arr_efMarker = array();
		preg_match_all('|###ENABLE_FIELDS\:(.*)###|U', $query, $arrResult, PREG_PATTERN_ORDER);
		if (isset($arrResult[0])) {
			$arr_efMarker = $arrResult[0];
		}

		// replace gp:marker
		foreach ($arr_gpMarker as $str_gpMarker) {
			$value = NULL;
			if (isset($marker[$str_gpMarker])) {
				$value = $marker[$str_gpMarker];
			}
			$query = str_replace($str_gpMarker, $value, $query);
		}

		// replace enable_fields:marker
		foreach ($arr_efMarker as $str_efMarker) {
			$str_efTable = trim(strtolower($str_efMarker), '#');
			list($dummy, $str_efTable) = explode(':', $str_efTable);
			$andWhere_ef = $this->cObj->enableFields($str_efTable);
			$query = str_replace($str_efMarker, $andWhere_ef, $query);
		}

		$pObj->conf['db.']['sql'] = $query;
	}

	public function getGPVars(&$obj)
	{
		$params = array('puid', 'tableId', 'cid', 'title', 'price', 'qty', 'sku', 'taxclass', 'service_attribute_1', 'service_attribute_2', 'service_attribute_3');

		$obj->gpvar['multiple'] = $obj->cObj->cObjGetSingle($obj->conf['settings.']['multiple'], $obj->conf['settings.']['multiple.']);

		if (TYPO3_DLOG) {
			GeneralUtility::devLog('gpvar after multiple', $this->extKey, 0, $obj->gpvar);
		}
		// if param multiple is set to a value greater 0 get GPVars of #multiple products
		if ($obj->gpvar['multiple']) {
			$post = GeneralUtility::_POST();

			for ($cnt = 1; $cnt <= $obj->gpvar['multiple']; $cnt++) {
				foreach ($params as $param) {
					$tmp = str_replace('GP:', '', $obj->conf['settings.'][$param . '.']['data']);
					switch (1) {
						case $obj->conf['settings.'][$param . '.']['intval']:
							$obj->gpvar['multi'][$cnt][$param] = intval($post[$tmp][$cnt]);
							break;
						case $obj->conf['settings.'][$param . '.']['floatval']:
							$obj->gpvar['multi'][$cnt][$param] = floatval($post[$tmp][$cnt]);
							break;
						default:
							$obj->gpvar['multi'][$cnt][$param] = $post[$tmp][$cnt];
							break;
					}
				}

				foreach ($obj->conf['settings.']['variants.'] as $key => $value) {
					if (!is_int($key) ? (ctype_digit($key)) : true) {
						$tmp = str_replace('GP:', '', $obj->conf['settings.']['variants.'][$key . '.']['data']);
						list($main, $sub) = explode('|', $tmp);
						$obj->gpvar['multi'][$cnt]['variants'][$key] = $post[$main][$cnt][$sub];
					}
				}
			}
		} else {
			foreach ($params as $param) {
				$obj->gpvar[$param] = $obj->cObj->cObjGetSingle($obj->conf['settings.'][$param], $obj->conf['settings.'][$param . '.']);
			}

			foreach ($obj->conf['settings.']['variants.'] as $key => $value) {
				if (!is_int($key) ? (ctype_digit($key)) : true) {
					$obj->gpvar['variants'][$key] = $obj->cObj->cObjGetSingle($obj->conf['settings.']['variants.'][$key], $obj->conf['settings.']['variants.'][$key . '.']);
				}
			}
		}
	}

	/**
	 * @param $obj
	 * @return array
	 */
	public function parseTaxes(&$obj)
	{
		$taxes = array();
		foreach ($obj->conf['taxclass.'] as $key => $value) {
			$taxes[rtrim($key, '.')] = new Tx_WtCart_Domain_Model_Tax(rtrim($key, '.'), $value['value'], $value['calc'], $value['name']);
		}

		if (TYPO3_DLOG) {
			GeneralUtility::devLog('parsed Taxes', $obj->extKey, 0, $taxes);
		}

		return $taxes;
	}

	/**
	 * @param $class
	 * @param $obj
	 * @internal param $taxes
	 * @return array
	 */
	public function parseServices($class, &$obj)
	{
		$services = array();
		$type = strtolower($class);
		if ($obj->conf[$type . '.']['options.']) {
			foreach ($obj->conf[$type . '.']['options.'] as $key => $value) {
				$className = 'Tx_WtCart_Domain_Model_' . $class;
				$service = new $className(rtrim($key, '.'), $value['title'], $obj->taxes[$value['taxclass']], $value['status'], $value['note'], $obj->gpvar['isNetPrice']);
				if (isset($value['extra.'])) {
					$service->setExtratype($value['extra']);
					foreach ($value['extra.'] as $extrakey => $extravalue) {
						$extra = new Tx_WtCart_Domain_Model_Extra(rtrim($extrakey, '.'), $extravalue['value'], $extravalue['extra'], $obj->taxes[$value['taxclass']], $obj->gpvar['isNetPrice']);
						$service->addExtra($extra);
					}
				} elseif (!floatval($value['extra'])) {
					$service->setExtratype($value['extra']);
					$extra = new Tx_WtCart_Domain_Model_Extra(0, 0, 0, $obj->taxes[$value['taxclass']], $obj->gpvar['isNetPrice']);
					$service->addExtra($extra);
				} else {
					$service->setExtratype('simple');
					$extra = new Tx_WtCart_Domain_Model_Extra(0, 0, $value['extra'], $obj->taxes[$value['taxclass']], $obj->gpvar['isNetPrice']);
					$service->addExtra($extra);
				}
				$service->setFreeFrom($value['free_from']);
				$service->setFreeUntil($value['free_until']);
				$service->setAvailableFrom($value['available_from']);
				$service->setAvailableUntil($value['available_until']);

				if ($value['preset'] == 1) {
					$service->setIsPreset(TRUE);
				}

				if ($value['additional.']) {
					$additional = array();
					foreach ($value['additional.'] as $additionalKey => $additionalValue) {
						if ($additionalValue['value']) {
							$additional[rtrim($additionalKey, '.')] = $additionalValue['value'];
						}
					}
				}

				$service->setAdditionalArray($additional);

				$services[rtrim($key, '.')] = $service;
			}
		}

		if (TYPO3_DLOG) {
			GeneralUtility::devLog('parsed ' . $type . 's', $obj->extKey, 0, $services);
		}

		return $services;
	}

	public function createProduct(&$obj)
	{

		$newProduct = new Tx_WtCart_Domain_Model_Product($obj->gpvar['puid'], $obj->gpvar['tableId'], $obj->gpvar['cid'], $obj->gpvar['sku'], $obj->gpvar['title'], $obj->gpvar['price'], $obj->taxes[$obj->gpvar['taxclass']], $obj->gpvar['qty'], $obj->gpvar['isNetPrice']);

		if ($obj->gpvar['variants']) {
			$price_calc_method = $obj->gpvar['price_calc_method'];
			$price = $obj->gpvar['price'];
			foreach ($obj->gpvar['variants'] as $key => $value) {
				if ($obj->gpvar['variants'][$key]) {
					if ($key == 1) {
						if ($obj->gpvar['has_fe_variants']) {
							$newVariant[$key] = new Tx_WtCart_Domain_Model_Variant(sha1($value), '', '', $price_calc_method, $price, $obj->taxes[$obj->gpvar['taxclass']], $obj->gpvar['qty'], $obj->gpvar['isNetPrice']);
							$newVariant[$key]->setHasFeVariants($obj->gpvar['has_fe_variants'] - 1);
							$newVariant[$key]->setTitle($value);
							$newVariant[$key]->setSku(str_replace(' ', '', $value));
						} else {
							$dbconf = isset($dbconf) ? $dbconf['db.']['variants.'] : $obj->conf['db.']['variants.'];

							// if value is a integer, get details from database
							if (!is_int($value) ? (ctype_digit($value)) : true) {
								// creating a new Tx_WtCart_Domain_Model_Variant and using Price and Taxclass form Product
								$newVariant[$key] = new Tx_WtCart_Domain_Model_Variant($obj->gpvar['variants'][$key], '', '', $price_calc_method, $price, $obj->taxes[$obj->gpvar['taxclass']], $obj->gpvar['qty'], $obj->gpvar['isNetPrice']);
								// get further data of variant
								$obj->div->getVariantDetails($newVariant[$key], $dbconf);
							} else {
								var_dump($obj->div->msg("Error, id was expected."));
							}
						}

						$price = $newVariant[$key]->getPrice();

						$newProduct->addVariant($newVariant[$key]);
					} elseif ($key > 1) {
						// check if variant key-1 has fe_variants defined then use input as fe variant
						if ($newVariant[$key - 1]->getHasFeVariants()) {
							$newVariant[$key] = new Tx_WtCart_Domain_Model_Variant(sha1($value), '', '', $price_calc_method, $price, $obj->taxes[$obj->gpvar['taxclass']], $obj->gpvar['qty'], $obj->gpvar['isNetPrice']);
							$newVariant[$key]->setHasFeVariants($newVariant[$key - 1]->getHasFeVariants() - 1);
							$newVariant[$key]->setTitle($value);
							$newVariant[$key]->setSku(str_replace(' ', '', $value));
						} else {
							$dbconf = isset($dbconf) ? $dbconf['db.']['variants.'] : $obj->conf['db.']['variants.'];

							// if value is a integer, get details from database
							if (!is_int($value) ? (ctype_digit($value)) : true) {
								// creating a new Tx_WtCart_Domain_Model_Variant and using Price and Taxclass form Product
								$newVariant[$key] = new Tx_WtCart_Domain_Model_Variant($obj->gpvar['variants'][$key], '', '', $price_calc_method, $price, $obj->taxes[$obj->gpvar['taxclass']], $obj->gpvar['qty'], $obj->gpvar['isNetPrice']);
								// get further data of variant
								$obj->div->getVariantDetails($newVariant[$key], $dbconf);
							} else {
								var_dump($obj->div->msg("Error, id was expected."));
							}
						}

						$price = $newVariant[$key]->getPrice();

						$newVariant[$key - 1]->addVariant($newVariant[$key]);
					}
				}
			}
		}
		return $newProduct;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_cart/lib/class.tx_wtcart_div.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wt_cart/lib/class.tx_wtcart_div.php']);
}
