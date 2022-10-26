<?php

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\Integrations\Freeagent\DataWrapper;

trait PayoutVoConsumer {

	/**
	 * @var DataWrapper\PayoutVO
	 */
	private $oPayoutVo;

	/**
	 * @return DataWrapper\PayoutVO
	 */
	public function getPayoutVO() {
		return $this->oPayoutVo;
	}

	/**
	 * @param DataWrapper\PayoutVO $oVO
	 * @return $this
	 */
	public function setPayoutVO( $oVO ) {
		$this->oPayoutVo = $oVO;
		return $this;
	}
}