<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers\{
	BankTransactionVoConsumer,
	BridgeConsumer,
	FreeagentConfigVoConsumer,
	PayoutVoConsumer,
};
use FernleafSystems\Integrations\Freeagent\Reconciliation\Bills;

/**
 * Class ProcessBillForPayout
 * @package FernleafSystems\Integrations\Freeagent\Reconciliation
 */
class ProcessBillForPayout {

	use ConnectionConsumer;
	use BankTransactionVoConsumer;
	use BridgeConsumer;
	use FreeagentConfigVoConsumer;
	use PayoutVoConsumer;

	/**
	 * @throws \Exception
	 */
	public function run() {
		$payout = $this->getPayoutVO();

		$this->refreshBankTxn(); // We do this to ensure we have the latest working BankTxn;

		$bill = $this->retrieveExistingBill();
		if ( empty( $bill ) ) {
			$bill = ( new Bills\CreateForPayout() )
				->setConnection( $this->getConnection() )
				->setPayoutVO( $payout )
				->setFreeagentConfigVO( $this->getFreeagentConfigVO() )
				->create();
			$this->getBridge()->storeExternalBillId( $payout, $bill );
		}

		( new Bills\ExplainBankTxnWithStripeBill() )
			->setConnection( $this->getConnection() )
			->setPayoutVO( $payout )
			->setBankTransactionVo( $this->getBankTransactionVo() )
			->setFreeagentConfigVO( $this->getFreeagentConfigVO() )
			->process( $bill );
	}

	/**
	 * @return Entities\Bills\BillVO|null
	 */
	protected function retrieveExistingBill() {
		$oBill = null;
		$nExtBillId = $this->getBridge()->getExternalBillId( $this->getPayoutVO() );
		if ( !empty( $nExtBillId ) ) {
			$oBill = ( new Entities\Bills\Retrieve() )
				->setConnection( $this->getConnection() )
				->setEntityId( $nExtBillId )
				->retrieve();
		}
		return $oBill;
	}

	/**
	 * @return $this
	 */
	protected function refreshBankTxn() {
		return $this->setBankTransactionVo(
			( new Entities\BankTransactions\Retrieve() )
				->setConnection( $this->getConnection() )
				->setEntityId( $this->getBankTransactionVo()->getId() )
				->sendRequestWithVoResponse()
		);
	}
}