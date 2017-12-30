<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities\BankTransactions\Retrieve;
use FernleafSystems\Integrations\Freeagent\Consumers\BankTransactionVoConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\BridgeConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\FreeagentConfigVoConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\PayoutVoConsumer;
use FernleafSystems\Integrations\Freeagent\Reconciliation\Bills\CreateForPayout;
use FernleafSystems\Integrations\Freeagent\Reconciliation\Bills\ExplainBankTxnWithStripeBill;

/**
 * Class ProcessBillForPayout
 * @package FernleafSystems\Integrations\Freeagent\Reconciliation
 */
class ProcessBillForPayout {

	use BankTransactionVoConsumer,
		BridgeConsumer,
		ConnectionConsumer,
		FreeagentConfigVoConsumer,
		PayoutVoConsumer;

	/**
	 * @throws \Exception
	 */
	public function run() {

		$this->refreshBankTxn(); // We do this to ensure we have the latest working BankTxn;

		$oBill = ( new CreateForPayout() )
			->setConnection( $this->getConnection() )
			->setPayoutVO( $this->getPayoutVO() )
			->setFreeagentConfigVO( $this->getFreeagentConfigVO() )
			->createBill();

		( new ExplainBankTxnWithStripeBill() )
			->setConnection( $this->getConnection() )
			->setPayoutVO( $this->getPayoutVO() )
			->setBankTransactionVo( $this->getBankTransactionVo() )
			->setFreeagentConfigVO( $this->getFreeagentConfigVO() )
			->process( $oBill );
	}

	protected function refreshBankTxn() {
		return $this->setBankTransactionVo(
			( new Retrieve() )
				->setConnection( $this->getConnection() )
				->setEntityId( $this->getBankTransactionVo()->getId() )
				->sendRequestWithVoResponse()
		);
	}
}