<?php

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\Integrations\Freeagent\DataWrapper\PayoutVO;

/**
 * Trait PayoutVoConsumer
 * @package FernleafSystems\Integrations\Freeagent\Consumers
 */
trait PayoutVoConsumer {

	/**
	 * @var PayoutVO
	 */
	private $oPayoutVo;

	/**
	 * @return PayoutVO
	 */
	public function getPayoutVO() {
		return $this->oPayoutVo;
	}

	/**
	 * @param PayoutVO $oVO
	 * @return $this
	 */
	public function setPayoutVO( $oVO ) {
		$this->oPayoutVo = $oVO;
		return $this;
	}
}