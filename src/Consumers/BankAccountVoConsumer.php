<?php

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\ApiWrappers\Freeagent\Entities\BankAccounts\BankAccountVO;

trait BankAccountVoConsumer {

	/**
	 * @var BankAccountVO
	 */
	private $oFreeagentBankAccountVO;

	/**
	 * @return BankAccountVO
	 */
	public function getBankAccountVo() {
		return $this->oFreeagentBankAccountVO;
	}

	/**
	 * @param BankAccountVO $oVo
	 * @return $this
	 */
	public function setBankAccountVo( $oVo ) {
		$this->oFreeagentBankAccountVO = $oVo;
		return $this;
	}
}