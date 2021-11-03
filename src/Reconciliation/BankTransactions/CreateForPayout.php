<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\BankTransactions;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers;

class CreateForPayout {

	use ConnectionConsumer;
	use Consumers\BankAccountVoConsumer;
	use Consumers\PayoutVoConsumer;

	/**
	 * @return Entities\BankTransactions\BankTransactionVO|null
	 * @throws \Exception
	 */
	public function create() {
		$oPayout = $this->getPayoutVO();
		$bSuccess = ( new Entities\BankTransactions\Create() )
			->setConnection( $this->getConnection() )
			->create(
				$this->getBankAccountVo(),
				$oPayout->date_arrival,
				$oPayout->getTotalNet(),//$oPayout->amount/100
				sprintf( 'Automatically create bank transaction for %s Payout %s',
					$oPayout->gateway, $oPayout->id )
			);

		$oBankTxn = null;
		if ( $bSuccess ) {
			sleep( 5 ); // to be extra sure it properly exists when we now try to find it.
			$oBankTxn = ( new FindForPayout() )
				->setConnection( $this->getConnection() )
				->setBankAccountVo( $this->getBankAccountVo() )
				->setPayoutVO( $oPayout )
				->find();
		}
		return $oBankTxn;
	}
}