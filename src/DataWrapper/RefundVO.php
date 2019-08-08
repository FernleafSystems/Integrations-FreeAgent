<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * Class RefundVO
 * @package FernleafSystems\Integrations\Freeagent\DataWrapper
 * @property string $id
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
	public function setGatewayChargeId( $sVal ) {
		return $this->setParam( 'charge_id', $sVal );
	}

	/**
	 * @param string $sVal
	 * @return $this
	 */
	public function setId( $sVal ) {
		return $this->setParam( 'id', $sVal );
	}
}