<?php

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\Integrations\Freeagent\Reconciliation\Bridge\BridgeInterface;

/**
 * Trait BridgeConsumer
 * @package FernleafSystems\Integrations\Freeagent\Consumers
 */
trait BridgeConsumer {

	/**
	 * @var BridgeInterface
	 */
	private $oMiddleManShopBridge;

	/**
	 * @return BridgeInterface
	 */
	public function getBridge() {
		return $this->oMiddleManShopBridge;
	}

	/**
	 * @param BridgeInterface $oBridge
	 * @return $this
	 */
	public function setBridge( $oBridge ) {
		$this->oMiddleManShopBridge = $oBridge;
		return $this;
	}
}