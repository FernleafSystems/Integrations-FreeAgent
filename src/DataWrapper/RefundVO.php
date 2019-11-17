<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * Class RefundVO
 * @package FernleafSystems\Integrations\Freeagent\DataWrapper
 * @property string $id
 * @property string $currency
 * @property string $date
 * @property string $gateway
 */
class RefundVO {

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
	public function getGatewayChargeId() {
		return $this->getStringParam( 'charge_id' );
	}

	/**
	 * @return string
	 */
	public function getCurrency() {
		return strtoupper( $this->getStringParam( 'currency' ) );
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getDate() {
		return $this->date;
	}

	/**
	 * @return string
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
	 */
	public function setGatewayChargeId( $sVal ) {
		return $this->setParam( 'charge_id', $sVal );
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
}