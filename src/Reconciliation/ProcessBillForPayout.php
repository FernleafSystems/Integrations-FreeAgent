<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Bills as BillEntity;
use FernleafSystems\ApiWrappers\Freeagent\Entities\BankTransactions;
use FernleafSystems\Integrations\Freeagent\Consumers\{
	BankTransactionVoConsumer,
	BridgeConsumer,
	FreeagentConfigVoConsumer,
	PayoutVoConsumer,
};

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
				->setFreeagentConfigVO( $this->getFreeagentConfigVO() )
				->setConnection( $this->getConnection() )
				->setPayoutVO( $payout )
				->create();
			$this->getBridge()->storeExternalBillId( $payout, $bill );
		}

		( new Bills\ExplainBankTxnWithBill() )
			->setBankTransactionVo( $this->getBankTransactionVo() )
			->setFreeagentConfigVO( $this->getFreeagentConfigVO() )
			->setConnection( $this->getConnection() )
			->setPayoutVO( $payout )
			->process( $bill );
	}

	protected function retrieveExistingBill() :?BillEntity\BillVO {
		$bill = null;
		$extBillID = $this->getBridge()->getExternalBillId( $this->getPayoutVO() );
		if ( !empty( $extBillID ) ) {
			$bill = ( new BillEntity\Retrieve() )
				->setEntityId( $extBillID )
				->setConnection( $this->getConnection() )
				->retrieve();
		}
		return $bill;
	}

	protected function refreshBankTxn() :static {
		return $this->setBankTransactionVo(
			( new BankTransactions\Retrieve() )
				->setEntityId( $this->getBankTransactionVo()->getId() )
				->setConnection( $this->getConnection() )
				->retrieve()
		);
	}
}