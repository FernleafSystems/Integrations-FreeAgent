<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\DynPropertiesClass;

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
class BaseTxnVO extends DynPropertiesClass {

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
	 * @param float $value
	 * @return $this
	 */
	public function setAmount_Fee( $value ) :self {
		$this->amount_fee = $value;
		return $this;
	}

	/**
	 * @param float $value
	 * @return $this
	 */
	public function setAmount_Gross( $value ) :self {
		$this->amount_gross = $value;
		return $this;
	}

	/**
	 * @param float $value
	 * @return $this
	 */
	public function setAmount_Net( $value ) :self {
		$this->amount_net = $value;
		return $this;
	}

	/**
	 * @param string $value
	 * @return $this
	 */
	public function setCurrency( $value ) :self {
		$this->currency = $value;
		return $this;
	}

	/**
	 * @param int $value
	 * @return $this
	 * @deprecated
	 */
	public function setDate( $value ) :self {
		$this->date = $value;
		return $this;
	}

	/**
	 * @param string $value
	 * @return $this
	 * @deprecated
	 */
	public function setId( $value ) :self {
		$this->id = $value;
		return $this;
	}
}