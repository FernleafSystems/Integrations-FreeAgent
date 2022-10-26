<?php

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\Integrations\Freeagent\DataWrapper\FreeagentConfigVO;

trait FreeagentConfigVoConsumer {

	/**
	 * @var FreeagentConfigVO
	 */
	private $oFreeagentConfigVoConsumer;

	/**
	 * @return FreeagentConfigVO
	 */
	public function getFreeagentConfigVO() {
		return $this->oFreeagentConfigVoConsumer;
	}

	/**
	 * @param FreeagentConfigVO $oVO
	 * @return $this
	 */
	public function setFreeagentConfigVO( $oVO ) {
		$this->oFreeagentConfigVoConsumer = $oVO;
		return $this;
	}
}