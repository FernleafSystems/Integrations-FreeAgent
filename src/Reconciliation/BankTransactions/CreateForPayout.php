<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\BankTransactions;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities\BankTransactions\BankTransactionVO;
use FernleafSystems\ApiWrappers\Freeagent\Entities\BankTransactions\Create;
use FernleafSystems\Integrations\Freeagent\Consumers\BankAccountVoConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\PayoutVoConsumer;

/**
 * Class CreateForPayout
 * @package FernleafSystems\Integrations\Freeagent\Reconciliation\BankTransactions
 */
class CreateForPayout {

	use BankAccountVoConsumer,
		ConnectionConsumer,
		PayoutVoConsumer;

	/**
	 * @return BankTransactionVO|null
	 * @throws \Exception
	 */
	public function create() {
		$oPayout = $this->getPayoutVO();
		/** @var BankTransactionVO $oBankTxn */
		$bSuccess = ( new Create() )
			->setConnection( $this->getConnection() )
			->create(
				$this->getBankAccountVo(),
				$oPayout->getDateArrival(),
				$oPayout->getAmount_Net(),//$oPayout->amount/100
				sprintf( 'Automatically create bank transaction for Stripe Payout %s', $oPayout->getId() )
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