<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * Class BaseTxnVO
 * @package FernleafSystems\Integrations\Freeagent\DataWrapper
 * @property string $id
 * @property int    $amount_net
 * @property int    $amount_gross
 * @property int    $amount_fee
 * @property string $currency
 * @property int    $date
 */
class BaseTxnVO {

	use StdClassAdapter;

	/**
	 * @return float
	 */
	public function getAmount_Net() {
		return $this->amount_net;
	}

	/**
	 * This is not gross with taxes, but gross with payment processor fees
	 * @return float
	 */
	public function getAmount_Gross() {
		return $this->amount_gross;
	}

	/**
	 * @return float
	 */
	public function getAmount_Fee() {
		return $this->amount_fee;
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getCurrency() {
		return strtoupper( $this->currency );
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
		$this->amount_fee = $mVal;
		return $this;
	}

	/**
	 * @param float $mVal
	 * @return $this
	 */
	public function setAmount_Gross( $mVal ) {
		$this->amount_gross = $mVal;
		return $this;
	}

	/**
	 * @param float $mVal
	 * @return $this
	 */
	public function setAmount_Net( $mVal ) {
		$this->amount_net = $mVal;
		return $this;
	}

	/**
	 * @param string $val
	 * @return $this
	 */
	public function setCurrency( $val ) {
		$this->currency = $val;
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
	public function setId( $sVal ) {
		$this->id = $sVal;
		return $this;
	}
}