<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Consumers;

use FernleafSystems\ApiWrappers\Freeagent\Entities\BankAccounts\BankAccountVO;

trait BankAccountVoConsumer {

	private ?BankAccountVO $faBankAccount = null;

	public function getBankAccountVo() :BankAccountVO {
		return $this->faBankAccount;
	}

	public function setBankAccountVo( BankAccountVO $VO ) :self {
		$this->faBankAccount = $VO;
		return $this;
	}
}