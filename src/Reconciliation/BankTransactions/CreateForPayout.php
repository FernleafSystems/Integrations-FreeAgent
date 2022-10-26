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
	 * @throws \Exception
	 */
	public function create() :?Entities\BankTransactions\BankTransactionVO {
		$payout = $this->getPayoutVO();
		$success = ( new Entities\BankTransactions\Create() )
			->setConnection( $this->getConnection() )
			->create(
				$this->getBankAccountVo(),
				$payout->date_arrival,
				$payout->getTotalNet(),//$payout->amount/100
				sprintf( 'Automatically create bank transaction for %s Payout %s',
					$payout->gateway, $payout->id )
			);

		$bankTxn = null;
		if ( $success ) {
			sleep( 5 ); // to be extra sure it properly exists when we now try to find it.
			$bankTxn = ( new FindForPayout() )
				->setConnection( $this->getConnection() )
				->setBankAccountVo( $this->getBankAccountVo() )
				->setPayoutVO( $payout )
				->find();
		}
		return $bankTxn;
	}
}