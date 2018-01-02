<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\BankTransactions;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers\BankAccountVoConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\PayoutVoConsumer;
use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * Class FindForPayout
 * @package FernleafSystems\Integrations\Freeagent\Reconciliation\BankTransactions
 */
class FindForPayout {

	use BankAccountVoConsumer,
		ConnectionConsumer,
		StdClassAdapter,
		PayoutVoConsumer;

	/**
	 * @return Entities\BankTransactions\BankTransactionVO|null
	 * @throws \Exception
	 */
	public function find() {
		$oBankTxn = null;

		foreach ( $this->getUnexplainedBankTxns() as $oTxn ) {
			if ( $oTxn->getAmountTotal()*100 == $this->getPayoutVO()->getTotalNet()*100 ) {
				$oBankTxn = $oTxn;
				break;
			}
		}

		return $oBankTxn;
	}

	/**
	 * @return Entities\BankTransactions\BankTransactionVO[]
	 */
	protected function getUnexplainedBankTxns() {
		/** @var Entities\BankTransactions\BankTransactionVO[] $aTxn */
		$aTxn = ( new Entities\BankTransactions\Find() )
			->setConnection( $this->getConnection() )
			->filterByDateRange( $this->getPayoutVO()->getDateArrival(), 1 )
			->setBankAccount( $this->getBankAccountVo() )
			->filterByUnexplained()
			->all();
		return $aTxn;
	}
}