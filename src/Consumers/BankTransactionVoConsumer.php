<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\ApiWrappers\Freeagent\Entities\BankTransactions\BankTransactionVO;

trait BankTransactionVoConsumer {

	private ?BankTransactionVO $faBankTxn = null;

	public function getBankTransactionVo() :BankTransactionVO {
		return $this->faBankTxn;
	}

	public function setBankTransactionVo( BankTransactionVO $VO ) :self {
		$this->faBankTxn = $VO;
		return $this;
	}
}