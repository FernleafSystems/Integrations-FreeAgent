<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\ApiWrappers\Freeagent\Entities\Common\Constants;

/**
 * @property string $gateway
 * @property string $item_name
 * @property string $country
 * @property bool   $is_vatmoss
 * @property string $ec_status
 * @property int    $payment_terms - days
 * @property int    $item_quantity
 * @property float  $item_subtotal
 * @property float  $item_taxrate
 * @property string $item_type
 * @property mixed  $local_payment_id
 */
class ChargeVO extends BaseTxnVO {

	public function __get( string $key ) {
		$val = parent::__get( $key );
		switch ( $key ) {
			case 'is_vatmoss':
				if ( is_null( $val ) ) {
					$val = $this->ec_status === Constants::VAT_STATUS_EC_MOSS;
				}
				break;
			case 'ec_status':
				if ( is_null( $val ) ) {
					$val = Constants::VAT_STATUS_UK_NON_EC;
				}
				break;
			case 'item_taxrate':
				if ( $val > 0 && $val < 1 ) {
					$val *= 100;
				}
				$val = (int)abs( round( $val ) );
				break;
			default:
				break;
		}
		return $val;
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getCountry() {
		return $this->country;
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

	public function getItemQuantity() :int {
		return (int)( $this->item_quantity ?? 1 );
	}

	public function getItemSubtotal() :float {
		return (float)( $this->item_subtotal ?? 1 );
	}

	/**
	 * Out of 100%
	 * @return float|int
	 */
	public function getItemTaxRate() :int {
		$val = (float)$this->item_taxrate;
		if ( $val > 0 && $val < 1 ) {
			$val *= 100;
		}
		return (int)abs( round( $val ) );
	}

	/**
	 * @return string
	 */
	public function getItemPeriodType() {
		return $this->item_type ?? 'Years';
	}

	public function getLocalPaymentId() {
		return $this->local_payment_id ?? 0;
	}

	public function getPaymentTerms() :int {
		return (int)$this->payment_terms ?? 5;
	}

	public function isEuVatMoss() :bool {
		return (bool)$this->is_vatmoss ?? false;
	}

	/**
	 * @param string $value
	 * @return $this
	 * @deprecated
	 */
	public function setGateway( $value ) :self {
		$this->gateway = $value;
		return $this;
	}

	/**
	 * @param bool $isMoss
	 * @return $this
	 */
	public function setIsEuVatMoss( $isMoss ) :self {
		$this->is_vatmoss = $isMoss;
		return $this;
	}

	/**
	 * @param string $value
	 * @return $this
	 */
	public function setItemName( $value ) :self {
		$this->item_name = $value;
		return $this;
	}

	/**
	 * @param int $value
	 * @return $this
	 */
	public function setItemQuantity( $value ) :self {
		$this->item_quantity = $value;
		return $this;
	}

	/**
	 * @param float $value
	 * @return $this
	 */
	public function setItemSubtotal( $value ) :self {
		$this->item_subtotal = $value;
		return $this;
	}

	/**
	 * @param float $value
	 * @return $this
	 */
	public function setItemTaxRate( $value ) :self {
		$this->item_taxrate = $value;
		return $this;
	}

	/**
	 * @param string $periodType
	 * @return $this
	 */
	public function setItemPeriodType( string $periodType ) :self {
		$this->item_type = $periodType;
		return $this;
	}

	/**
	 * @param mixed $value
	 * @return $this
	 */
	public function setLocalPaymentId( $value ) :self {
		$this->local_payment_id = $value;
		return $this;
	}

	/**
	 * @param int $terms
	 * @return $this
	 * @deprecated
	 */
	public function setPaymentTerms( $terms ) {
		$this->payment_terms = $terms;
		return $this;
	}
}