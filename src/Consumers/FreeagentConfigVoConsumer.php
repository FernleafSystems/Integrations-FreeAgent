<?php

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\Integrations\Freeagent\DataWrapper\FreeagentConfigVO;

/**
 * Trait FreeagentConfigVoConsumer
 * @package FernleafSystems\Integrations\Freeagent\Consumers
 */
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