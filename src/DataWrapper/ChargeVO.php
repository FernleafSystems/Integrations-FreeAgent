<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * Class PayoutVO
 * @package FernleafSystems\Integrations\Freeagent\DataWrapper
 */
class ChargeVO {

	use StdClassAdapter;

	/**
	 * @return float
	 */
	public function getAmount_Net() {
		return $this->getNumericParam( 'amount_net' ); //TODO: Ensure Stripe's value is /100
	}

	/**
	 * This is not gross with taxes, but gross with payment processor fees
	 * @return float
	 */
	public function getAmount_Gross() {
		return $this->getNumericParam( 'amount_gross' ); //TODO: Ensure Stripe's value is /100
	}

	/**
	 * @return float
	 */
	public function getAmount_Fee() {
		return $this->getNumericParam( 'amount_fee' ); //TODO: Ensure Stripe's value is /100
	}

	/**
	 * @return string
	 */
	public function getCountry() {
		return strtoupper( $this->getStringParam( 'country' ) );
	}

	/**
	 * @return string
	 */
	public function getCurrency() {
		return strtoupper( $this->getStringParam( 'currency' ) );
	}

	/**
	 * @return int
	 */
	public function getDate() {
		return $this->getParam( 'date' );
	}

	/**
	 * @return string
	 */
	public function getGateway() {
		return $this->getStringParam( 'gateway' );
	}

	/**
	 * @return string
	 */
	public function getId() {
		return $this->getStringParam( 'id' );
	}

	/**
	 * @return string
	 */
	public function getItemName() {
		return $this->getStringParam( 'item_name' );
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
		return (int)$this->getNumericParam( 'payment_terms', 14 );
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
	 * @param string $mVal
	 * @return $this
	 */
	public function setCurrency( $mVal ) {
		return $this->setParam( 'currency', $mVal );
	}

	/**
	 * @param int $mVal
	 * @return $this
	 */
	public function setDate( $mVal ) {
		return $this->setParam( 'date', $mVal );
	}

	/**
	 * @param string $sVal
	 * @return $this
	 */
	public function setGateway( $sVal ) {
		return $this->setParam( 'gateway', $sVal );
	}

	/**
	 * @param string $sVal
	 * @return $this
	 */
	public function setId( $sVal ) {
		return $this->setParam( 'id', $sVal );
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
	 */
	public function setPaymentTerms( $nVal ) {
		return $this->setParam( 'payment_terms', $nVal );
	}
}