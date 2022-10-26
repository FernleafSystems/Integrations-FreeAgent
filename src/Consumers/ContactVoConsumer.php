<?php

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\ApiWrappers\Freeagent\Entities\Contacts\ContactVO;

trait ContactVoConsumer {

	/**
	 * @var ContactVO
	 */
	private $oFreeagentContactVO;

	/**
	 * @return ContactVO
	 */
	public function getContactVo() {
		return $this->oFreeagentContactVO;
	}

	/**
	 * @param ContactVO $oVo
	 * @return $this
	 */
	public function setContactVo( $oVo ) {
		$this->oFreeagentContactVO = $oVo;
		return $this;
	}
}