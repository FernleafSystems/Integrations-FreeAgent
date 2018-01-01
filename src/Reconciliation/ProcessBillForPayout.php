<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
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
		$oPayout = $this->getPayoutVO();

		$this->refreshBankTxn(); // We do this to ensure we have the latest working BankTxn;

		$oBill = $this->retrieveExistingBill();
		if ( empty( $oBill ) ) {
			$oBill = ( new CreateForPayout() )
				->setConnection( $this->getConnection() )
				->setPayoutVO( $oPayout )
				->setFreeagentConfigVO( $this->getFreeagentConfigVO() )
				->create();
			$this->getBridge()->storeExternalBillId( $oBill, $oPayout );
		}

		( new ExplainBankTxnWithStripeBill() )
			->setConnection( $this->getConnection() )
			->setPayoutVO( $oPayout )
			->setBankTransactionVo( $this->getBankTransactionVo() )
			->setFreeagentConfigVO( $this->getFreeagentConfigVO() )
			->process( $oBill );
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