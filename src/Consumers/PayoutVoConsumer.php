<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\Integrations\Freeagent\DataWrapper\PayoutVO;

trait PayoutVoConsumer {

	private ?PayoutVO $payoutVO = null;

	public function getPayoutVO() :PayoutVO {
		return $this->payoutVO;
	}

	public function setPayoutVO( PayoutVO $VO ) :self {
		$this->payoutVO = $VO;
		return $this;
	}
}