<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

/**
 * Class ChargeVO
 * @package FernleafSystems\Integrations\Freeagent\DataWrapper
 * @property string     $gateway
 * @property string     $item_name
 * @property int        $payment_terms - days
 */
class ChargeVO extends BaseTxnVO {

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
	public function getGateway() {
		return $this->gateway;
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
	 * @param string $val
	 * @return $this
	 * @deprecated
	 */
	public function setGateway( $val ) {
		$this->gateway = $val;
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
}