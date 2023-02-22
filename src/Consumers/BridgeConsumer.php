<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\Integrations\Freeagent\Reconciliation\Bridge\BridgeInterface;

trait BridgeConsumer {

	private ?BridgeInterface $shopBridge = null;

	public function getBridge() :BridgeInterface {
		return $this->shopBridge;
	}

	public function setBridge( BridgeInterface $bridge ) :self {
		$this->shopBridge = $bridge;
		return $this;
	}
}