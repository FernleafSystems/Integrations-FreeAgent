<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\Integrations\Freeagent\DataWrapper\FreeagentConfigVO;

trait FreeagentConfigVoConsumer {

	private ?FreeagentConfigVO $faConfig = null;

	public function getFreeagentConfigVO() :FreeagentConfigVO {
		return $this->faConfig;
	}

	public function setFreeagentConfigVO( FreeagentConfigVO $VO ) :self {
		$this->faConfig = $VO;
		return $this;
	}
}