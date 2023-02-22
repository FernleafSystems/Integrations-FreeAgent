<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\ApiWrappers\Freeagent\Entities\Contacts\ContactVO;

trait ContactVoConsumer {

	private ?ContactVO $faContactVO = null;

	public function getContactVo() :ContactVO {
		return $this->faContactVO;
	}

	public function setContactVo( ContactVO $VO ) :self {
		$this->faContactVO = $VO;
		return $this;
	}
}