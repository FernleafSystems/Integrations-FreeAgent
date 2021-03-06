<?php

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\ApiWrappers\Freeagent\Entities\BankTransactions\BankTransactionVO;

/**
 * Trait BankTransactionVoConsumer
 * @package FernleafSystems\Integrations\Freeagent\Consumers
 */
trait BankTransactionVoConsumer {

	/**
	 * @var BankTransactionVO
	 */
	private $oFreeagentBankTransactionVO;

	/**
	 * @return BankTransactionVO
	 */
	public function getBankTransactionVo() {
		return $this->oFreeagentBankTransactionVO;
	}

	/**
	 * @param BankTransactionVO $oVo
	 * @return $this
	 */
	public function setBankTransactionVo( $oVo ) {
		$this->oFreeagentBankTransactionVO = $oVo;
		return $this;
	}
}