<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

/**
 * Class RefundVO
 * @package FernleafSystems\Integrations\Freeagent\DataWrapper
 * @property string $gateway
 * @property string $charge_id
 */
class RefundVO extends BaseTxnVO {

	/**
	 * @return string
	 */
	public function getGatewayChargeId() {
		return $this->charge_id;
	}

	/**
	 * @return string
	 */
	public function getGateway() {
		return $this->gateway;
	}

	/**
	 * @param string $value
	 * @return $this
	 * @deprecated
	 */
	public function setGateway( $value ) {
		$this->gateway = $value;
		return $this;
	}

	/**
	 * @param string $value
	 * @return $this
	 */
	public function setGatewayChargeId( $value ) :self {
		$this->charge_id = $value;
		return $this;
	}
}