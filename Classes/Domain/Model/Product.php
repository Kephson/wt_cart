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
 * @author    Daniel Lorenz <daniel.lorenz@extco.de>
 * @package    TYPO3
 * @subpackage    tx_wtcart
 * @version    1.5.0
 */
class Tx_WtCart_Domain_Model_Product
{

	/**
	 * $productId = Product identifier defines the unique identifier each product have
	 *
	 * @var integer
	 */
	private $productId;

	/**
	 * $tableId = Table configuration Id is defined by TypoScript and is used to
	 * define the table the product comes from
	 *
	 * @var integer
	 */
	private $tableId;

	/**
	 * @var integer
	 */
	private $contentId;

	/**
	 * @var string
	 */
	private $title;

	/**
	 * @var string
	 */
	private $sku;

	/**
	 * @var float
	 */
	private $price;

	/**
	 * @var Tx_WtCart_Domain_Model_Tax
	 */
	private $taxClass;

	/**
	 * @var integer
	 */
	private $qty;

	/**
	 * @var float
	 */
	private $gross;

	/**
	 * @var float
	 */
	private $net;

	/**
	 * @var float
	 */
	private $tax;
	private $error;

	/**
	 * @var float
	 */
	private $serviceAttribute1;

	/**
	 * @var float
	 */
	private $serviceAttribute2;

	/**
	 * @var float
	 */
	private $serviceAttribute3;

	/**
	 * @var boolean
	 */
	private $isNetPrice;

	/**
	 * @var array Tx_WtCart_Domain_Model_Variant
	 */
	private $variants;

	/**
	 * @var array Additional
	 */
	private $additional = array();

	/**
	 * __construct
	 *
	 * @param int $productId
	 * @param int $tableId
	 * @param int $contentId
	 * @param string $sku
	 * @param string $title
	 * @param float $price
	 * @param Tx_WtCart_Domain_Model_Tax $taxClass
	 * @param int $qty
	 * @param bool $isNetPrice
	 * @throws InvalidArgumentException
	 */
	public function __construct($productId, $tableId = 0, $contentId = 0, $sku, $title, $price, Tx_WtCart_Domain_Model_Tax $taxClass, $qty, $isNetPrice = FALSE)
	{
		if (!$productId) {
			throw new \InvalidArgumentException(
			'You have to specify a valid $productId for constructor.', 1413999100
			);
		}

		if (!$sku) {
			throw new \InvalidArgumentException(
			'You have to specify a valid $sku for constructor.', 1413999110
			);
		}

		if (!$title) {
			throw new \InvalidArgumentException(
			'You have to specify a valid $title for constructor.', 1413999120
			);
		}
		if (!$price) {
			throw new \InvalidArgumentException(
			'You have to specify a valid $price for constructor.', 1413999130
			);
		}
		if (!$taxClass) {
			throw new \InvalidArgumentException(
			'You have to specify a valid $taxClass for constructor.', 1413999140
			);
		}
		if (!$qty) {
			throw new \InvalidArgumentException(
			'You have to specify a valid $qty for constructor.', 1413999150
			);
		}

		$this->productId = $productId;
		$this->tableId = $tableId;
		$this->contentId = $contentId;
		$this->sku = $sku;
		$this->title = $title;
		$this->price = $price;
		$this->taxClass = $taxClass;
		$this->qty = $qty;
		$this->isNetPrice = $isNetPrice;

		$this->calcGross();
		$this->calcTax();
		$this->calcNet();
	}

	/**
	 * @param string $sku
	 */
	public function setSku($sku)
	{
		$this->sku = $sku;
	}

	/**
	 * @return string
	 */
	public function getSku()
	{
		return $this->sku;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return boolean
	 */
	public function getIsNetPrice()
	{
		return $this->isNetPrice;
	}

	/**
	 * @param boolean
	 * @return void
	 */
	public function setIsNetPrice($isNetPrice)
	{
		$this->isNettoPrice = $isNetPrice;
	}

	/**
	 * @param array $newVariants
	 * @return mixed
	 */
	public function addVariants($newVariants)
	{
		foreach ($newVariants as $newVariant) {
			$this->addVariant($newVariant);
		}
	}

	/**
	 * @param Tx_WtCart_Domain_Model_Variant $newVariant
	 * @return mixed
	 */
	public function addVariant(Tx_WtCart_Domain_Model_Variant $newVariant)
	{
		$newVariantId = $newVariant->getId();
		$variant = $this->variants[$newVariantId];

		if ($variant) {
			if ($variant->getVariants()) {
				$variant->addVariants($newVariant->getVariants());
			} else {
				$newQty = $variant->getQty() + $newVariant->getQty();
				$variant->setQty($newQty);
			}
		} else {
			$this->variants[$newVariantId] = $newVariant;
			$newVariant->setProduct($this);
		}

		$this->reCalc();
	}

	/**
	 * @param $variantQtyArray
	 * @internal param $id
	 * @internal param $newQty
	 */
	public function changeVariantsQty($variantQtyArray)
	{
		foreach ($variantQtyArray as $variantId => $qty) {
			$variant = $this->variants[$variantId];

			if (is_array($qty)) {
				$variant->changeVariantsQty($qty);
				$this->reCalc();
			} else {
				$variant->changeQty($qty);
				$this->reCalc();
			}
		}
	}

	/**
	 * @return array
	 */
	public function getVariants()
	{
		return $this->variants;
	}

	/**
	 * @param $variantId
	 * @return Variant
	 */
	public function getVariantById($variantId)
	{
		return $this->variants[$variantId];
	}

	/**
	 * @param $variantId
	 * @return Variant
	 */
	public function getVariant($variantId)
	{
		return $this->getVariantById($variantId);
	}

	/**
	 * @param $variantsArray
	 * @return bool|int
	 * @internal param $productPuid
	 * @internal param null $variantId
	 * @internal param $id
	 */
	public function removeVariants($variantsArray)
	{
		foreach ($variantsArray as $variantId => $value) {
			$variant = $this->variants[$variantId];
			if ($variant) {
				if (is_array($value)) {
					$variant->removeVariants($value);

					if (!$variant->getVariants()) {
						unset($this->variants[$variantId]);
					}

					$this->reCalc();
				} else {
					unset($this->variants[$variantId]);

					$this->reCalc();
				}
			} else {
				return -1;
			}
		}

		return TRUE;
	}

	/**
	 * @param $variantId
	 * @return array
	 */
	public function removeVariantById($variantId)
	{
		unset($this->variants[$variantId]);

		$this->reCalc();
	}

	/**
	 * @param $variantId
	 * @return array
	 */
	public function removeVariant($variantId)
	{
		$this->removeVariantById($variantId);
	}

	/**
	 * @param $variantId
	 * @param $newQty
	 * @internal param $id
	 */
	public function changeVariantById($variantId, $newQty)
	{
		$this->variants[$variantId]->changeQty($newQty);

		$this->reCalc();
	}

	/**
	 * @return int
	 * @deprecated since wt_cart 2.1; will be removed in wt_cart 3.0; use getProductId instead
	 */
	public function getPuid()
	{
		return $this->getProductId();
	}

	/**
	 * @return int
	 */
	public function getProductId()
	{
		return $this->productId;
	}

	/**
	 * @return int
	 */
	public function getTableId()
	{
		return $this->tableId;
	}

	/**
	 * @return string
	 */
	public function getTableProductId()
	{
		return join('_', array($this->getTableId(), $this->getProductId()));
	}

	/**
	 * @return int
	 * @deprecated since wt_cart 2.1; will be removed in wt_cart 3.0; use getContentId instead
	 */
	public function getCid()
	{
		return $this->getContentId();
	}

	/**
	 * @return int
	 */
	public function getContentId()
	{
		return $this->contentId;
	}

	/**
	 * @return float
	 */
	public function getPrice()
	{
		return $this->price;
	}

	/**
	 * @return float
	 */
	public function getPriceTax()
	{
		return ($this->price / (1 + $this->taxClass->getCalc())) * ($this->taxClass->getCalc());
	}

	/**
	 * @return Tx_WtCart_Domain_Model_Tax
	 */
	public function getTaxClass()
	{
		return $this->taxClass;
	}

	/**
	 * @param $newQty
	 */
	public function changeQty($newQty)
	{
		if ($this->qty != $newQty) {
			$this->qty = $newQty;

			$this->reCalc();
		}
	}

	/**
	 * @return int
	 */
	public function getQty()
	{
		return $this->qty;
	}

	/**
	 * @return float
	 */
	public function getGross()
	{
		$this->calcGross();
		return $this->gross;
	}

	/**
	 * @return float
	 */
	public function getNet()
	{
		$this->calcNet();
		return $this->net;
	}

	/**
	 * @return array
	 */
	public function getTax()
	{
		$this->calcTax();
		return array('taxclassid' => $this->taxClass->getId(), 'tax' => $this->tax);
	}

	/**
	 * @return mixed
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @return float
	 */
	public function getServiceAttribute1()
	{
		return $this->serviceAttribute1;
	}

	/**
	 * @param float $serviceAttribute1
	 */
	public function setServiceAttribute1($serviceAttribute1)
	{
		$this->serviceAttribute1 = floatval($serviceAttribute1);
	}

	/**
	 * @return float
	 */
	public function getServiceAttribute2()
	{
		return $this->serviceAttribute2;
	}

	/**
	 * @param float $serviceAttribute2
	 */
	public function setServiceAttribute2($serviceAttribute2)
	{
		$this->serviceAttribute2 = floatval($serviceAttribute2);
	}

	/**
	 * @return float
	 */
	public function getServiceAttribute3()
	{
		return $this->serviceAttribute3;
	}

	/**
	 * @param float $serviceAttribute3
	 */
	public function setServiceAttribute3($serviceAttribute3)
	{
		$this->serviceAttribute3 = floatval($serviceAttribute3);
	}

	/**
	 * @deprecated since wt_cart 2.1; will be removed in wt_cart 3.0; use toArray instead
	 * @return array
	 */
	public function getProductAsArray()
	{
		return $this->toArray();
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$productArr = array(
			'puid' => $this->productId,
			'tableId' => $this->tableId,
			'tableProductId' => $this->getTableProductId(),
			'cid' => $this->contentId,
			'sku' => $this->sku,
			'title' => $this->title,
			'price' => $this->price,
			'taxclass' => $this->taxClass,
			'qty' => $this->qty,
			'price_total' => $this->gross,
			'price_total_gross' => $this->gross,
			'price_total_net' => $this->net,
			'tax' => $this->tax,
			'additional' => $this->additional
		);

		if ($this->variants) {
			$variantArr = array();

			foreach ($this->variants as $variant) {
				/** @var $variant Tx_WtCart_Domain_Model_Variant */
				array_push($variantArr, array($variant->getId() => $variant->toArray()));
			}

			array_push($productArr, array('variants' => $variantArr));
		}

		return $productArr;
	}

	/**
	 * @return string
	 */
	public function toJson()
	{
		json_encode($this->toArray());
	}

	/**
	 * @return void
	 */
	public function debug()
	{
		if (TYPO3_DLOG) {
			if ($this->variants) {
				foreach ($this->variants as $variant) {
					$variant->debug();
				}
			}

			GeneralUtility::devLog('product', 'wt_cart', 0, $this->toArray());
		}
	}

	/**
	 * @return void
	 */
	private function calcGross()
	{
		if ($this->isNetPrice == FALSE) {
			if ($this->variants) {
				$sum = 0.0;
				foreach ($this->variants as $variant) {
					$sum += $variant->getGross();
				}
				$this->gross = $sum;
			} else {
				$this->gross = $this->price * $this->qty;
			}
		} else {
			$this->calcNet();
			$this->calcTax();
			$this->gross = $this->net + $this->tax;
		}
	}

	/**
	 * @return void
	 */
	private function calcTax()
	{
		if ($this->isNetPrice == FALSE) {
			$this->tax = ($this->gross / (1 + $this->taxClass->getCalc())) * ($this->taxClass->getCalc());
		} else {
			$this->tax = ($this->net * $this->taxClass->getCalc());
		}
	}

	/**
	 * @return void
	 */
	private function calcNet()
	{
		if ($this->isNetPrice == TRUE) {
			if ($this->variants) {
				$sum = 0.0;
				foreach ($this->variants as $variant) {
					$sum += $variant->getNet();
				}
				$this->net = $sum;
			} else {
				$this->net = $this->price * $this->qty;
			}
		} else {
			$this->calcGross();
			$this->calcTax();
			$this->net = $this->gross - $this->tax;
		}
	}

	/**
	 * @return void
	 */
	private function reCalc()
	{
		if ($this->variants) {
			$qty = 0;
			foreach ($this->variants as $variant) {
				$qty += $variant->getQty();
			}

			if ($this->qty != $qty) {
				$this->qty = $qty;
			}
		}

		$this->calcGross();
		$this->calcTax();
		$this->calcNet();
	}

	/**
	 * @return array
	 */
	public function getAdditionalArray()
	{
		return $this->additional;
	}

	/**
	 * @param $additional
	 * @return void
	 */
	public function setAdditionalArray($additional)
	{
		$this->additional = $additional;
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function getAdditional($key)
	{
		return $this->additional[$key];
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function setAdditional($key, $value)
	{
		$this->additional[$key] = $value;
	}
}
