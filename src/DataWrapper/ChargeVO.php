<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * Class ChargeVO
 * @package FernleafSystems\Integrations\Freeagent\DataWrapper
 * @property string     $id
 * @property string     $currency
 * @property int|string $date          - YYYY-MM-DD
 * @property string     $gateway
 * @property string     $item_name
 * @property int        $payment_terms - days
 */
class ChargeVO {

	use StdClassAdapter;

	/**
	 * @return float
	 */
	public function getAmount_Net() {
		return $this->getNumericParam( 'amount_net' );
	}

	/**
	 * This is not gross with taxes, but gross with payment processor fees
	 * @return float
	 */
	public function getAmount_Gross() {
		return $this->getNumericParam( 'amount_gross' );
	}

	/**
	 * @return float
	 */
	public function getAmount_Fee() {
		return $this->getNumericParam( 'amount_fee' );
	}

	/**
	 * @return string
	 */
	public function getCountry() {
		return strtoupper( $this->getStringParam( 'country' ) );
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getCurrency() {
		return strtoupper( $this->currency );
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getGateway() {
		return $this->gateway;
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getItemName() {
		return $this->item_name;
	}

	/**
	 * @return float|int|null
	 */
	public function getItemQuantity() {
		return $this->getNumericParam( 'item_quantity', 1 );
	}

	/**
	 * @return float|int|null
	 */
	public function getItemSubtotal() {
		return $this->getNumericParam( 'item_subtotal' );
	}

	/**
	 * Out of 100%
	 * @return float|int
	 */
	public function getItemTaxRate() {
		$nVal = $this->getNumericParam( 'item_taxrate' );
		if ( $nVal > 0 && $nVal < 1 ) {
			$nVal *= 100;
		}
		return abs( round( $nVal ) );
	}

	/**
	 * @return string
	 */
	public function getItemPeriodType() {
		return $this->getStringParam( 'item_type', 'Years' );
	}

	/**
	 * @return int
	 */
	public function getLocalPaymentId() {
		return (int)$this->getNumericParam( 'local_payment_id', 0 );
	}

	/**
	 * @return int
	 */
	public function getPaymentTerms() {
		return (int)$this->getNumericParam( 'payment_terms', 5 );
	}

	/**
	 * @return bool
	 */
	public function isEuVatMoss() {
		return (bool)$this->getParam( 'is_vatmoss', false );
	}

	/**
	 * @param float $mVal
	 * @return $this
	 */
	public function setAmount_Fee( $mVal ) {
		return $this->setParam( 'amount_fee', $mVal );
	}

	/**
	 * @param float $mVal
	 * @return $this
	 */
	public function setAmount_Gross( $mVal ) {
		return $this->setParam( 'amount_gross', $mVal );
	}

	/**
	 * @param float $mVal
	 * @return $this
	 */
	public function setAmount_Net( $mVal ) {
		return $this->setParam( 'amount_net', $mVal );
	}

	/**
	 * @param string $sVal
	 * @return $this
	 * @deprecated
	 */
	public function setCurrency( $sVal ) {
		$this->currency = $sVal;
		return $this;
	}

	/**
	 * @param int $nVal
	 * @return $this
	 * @deprecated
	 */
	public function setDate( $nVal ) {
		$this->date = $nVal;
		return $this;
	}

	/**
	 * @param string $sVal
	 * @return $this
	 * @deprecated
	 */
	public function setGateway( $sVal ) {
		$this->gateway = $sVal;
		return $this;
	}

	/**
	 * @param string $sVal
	 * @return $this
	 * @deprecated
	 */
	public function setId( $sVal ) {
		$this->id = $sVal;
		return $this;
	}

	/**
	 * @param bool $bIsVatMost
	 * @return $this
	 */
	public function setIsEuVatMoss( $bIsVatMost ) {
		return $this->setParam( 'is_vatmoss', $bIsVatMost );
	}

	/**
	 * @param string $sVal
	 * @return $this
	 */
	public function setItemName( $sVal ) {
		return $this->setParam( 'item_name', $sVal );
	}

	/**
	 * @param string $sVal
	 * @return $this
	 */
	public function setItemQuantity( $sVal ) {
		return $this->setParam( 'item_quantity', $sVal );
	}

	/**
	 * @param float $nVal
	 * @return $this
	 */
	public function setItemSubtotal( $nVal ) {
		return $this->setParam( 'item_subtotal', $nVal );
	}

	/**
	 * @param float $nVal
	 * @return $this
	 */
	public function setItemTaxRate( $nVal ) {
		return $this->setParam( 'item_taxrate', $nVal );
	}

	/**
	 * @param string $sVal
	 * @return $this
	 */
	public function setItemPeriodType( $sVal ) {
		return $this->setParam( 'item_type', $sVal );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 */
	public function setLocalPaymentId( $nVal ) {
		return $this->setParam( 'local_payment_id', $nVal );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 * @deprecated
	 */
	public function setPaymentTerms( $nVal ) {
		return $this->setParam( 'payment_terms', $nVal );
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getDate() {
		return $this->date;
	}
}