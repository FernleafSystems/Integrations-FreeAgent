<?php

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\Integrations\Freeagent\DataWrapper;

trait ChargeVoConsumer {

	/**
	 * @var DataWrapper\ChargeVO
	 */
	private $oChargeVo;

	/**
	 * @return DataWrapper\ChargeVO
	 */
	public function getChargeVO() {
		return $this->oChargeVo;
	}

	/**
	 * @param DataWrapper\ChargeVO $oVO
	 * @return $this
	 */
	public function setChargeVO( $oVO ) {
		$this->oChargeVo = $oVO;
		return $this;
	}
}