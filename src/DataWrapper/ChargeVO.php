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
	public function getCurrency() {
		return strtolower( $this->getStringParam( 'currency_charge' ) );
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
	public function getId() {
		return $this->getStringParam( 'id' );
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
	 * @param string $mVal
	 * @return $this
	 */
	public function setId( $mVal ) {
		return $this->setParam( 'id', $mVal );
	}
}