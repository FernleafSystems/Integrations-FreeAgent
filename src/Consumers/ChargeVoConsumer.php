<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\Integrations\Freeagent\DataWrapper\ChargeVO;

trait ChargeVoConsumer {

	private ?ChargeVO $chargeVO = null;

	public function getChargeVO() :ChargeVO {
		return $this->chargeVO;
	}

	public function setChargeVO( ChargeVO $VO ) :self {
		$this->chargeVO = $VO;
		return $this;
	}
}