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
}