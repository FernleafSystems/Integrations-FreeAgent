<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bills;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities\BankTransactionExplanation;
use FernleafSystems\ApiWrappers\Freeagent\Entities\BankTransactions;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Bills;
use FernleafSystems\Integrations\Freeagent\Consumers;

/**
 * Class ExplainBankTxnWithForeignBill
 * @package FernleafSystems\Integrations\Freeagent\Reconciliation\Bills
 */
class ExplainBankTxnWithForeignBill {

	use Consumers\BankAccountVoConsumer,
		Consumers\BankTransactionVoConsumer,
		Consumers\PayoutVoConsumer,
		ConnectionConsumer;

	/**
	 * @param Bills\BillVO $oBill
	 * @return bool
	 * @throws \Exception
	 */
	public function createExplanation( $oBill ) {
		$oBankTransferExplTxn = $this->createAccountTransferExplanation( $oBill );
		$oLinkedTxn = $this->getNewLinkedBankTransferTransaction( $oBankTransferExplTxn );
		$oUpdatedBill = $this->updateBillWithNewValue( $oBill, $oLinkedTxn->getAmountTotal() );
		$this->createBillExplanation( $oUpdatedBill );
		return true;
	}

	/**
	 * @param Bills\BillVO $oBill
	 * @return BankTransactionExplanation\BankTransactionExplanationVO
	 * @throws \Exception
	 */
	protected function createBillExplanation( $oBill ) {
		$oExplanation = ( new BankTransactionExplanation\CreateManual() )
			->setConnection( $this->getConnection() )
			->setBankAccount( $this->getBankAccountVo() )
			->setBillPaid( $oBill )
			->setValue( $oBill->getAmountTotal() )
			->setDatedOn( $oBill->getDatedOn() )
			->create();
		if ( empty( $oExplanation ) ) {
			throw new \Exception( 'Creation of final foreign bill for Stripe failed' );
		}
		return $oExplanation;
	}

	/**
	 * @param Bills\BillVO $oBill
	 * @return BankTransactionExplanation\BankTransactionExplanationVO|null
	 * @throws \Exception
	 */
	protected function createAccountTransferExplanation( $oBill ) {

		$oBankTransferExplanationTxn = ( new BankTransactionExplanation\CreateTransferToAnotherAccount() )
			->setConnection( $this->getConnection() )
			->setBankTxn( $this->getBankTransactionVo() )
			->setTargetBankAccount( $this->getBankAccountVo() )
			->setValue( -1*$oBill->getAmountTotal() )// -1 as it's leaving the account
			->create();
		if ( empty( $oBankTransferExplanationTxn ) ) {
			throw new \Exception( 'Failed to explain bank transfer transaction in FreeAgent.' );
		}

		return $oBankTransferExplanationTxn;
	}

	/**
	 * @param BankTransactionExplanation\BankTransactionExplanationVO $oBankTransferExplTxn
	 * @return BankTransactions\BankTransactionVO|null
	 */
	protected function getNewLinkedBankTransferTransaction( $oBankTransferExplTxn ) {
		$oLinkedBankTxnExpl = ( new BankTransactionExplanation\RetrieveLinked() )
			->setConnection( $this->getConnection() )
			->setExplanation( $oBankTransferExplTxn )
			->sendRequestWithVoResponse();
		return ( new BankTransactions\Retrieve() )
			->setConnection( $this->getConnection() )
			->setEntityId( $oLinkedBankTxnExpl->getBankTransactionId() )
			->sendRequestWithVoResponse();
	}

	/**
	 * @param Bills\BillVO $oBill
	 * @param float        $nNewValue
	 * @return Bills\BillVO|null
	 * @throws \Exception
	 */
	protected function updateBillWithNewValue( $oBill, $nNewValue ) {
		$oBill = ( new Bills\Update() )
			->setConnection( $this->getConnection() )
			->setTotalValue( $nNewValue )
			->setEntityId( $oBill->getId() )
			->update();
		if ( empty( $oBill ) ) {
			throw new \Exception( 'Failed to update Bill with new total value' );
		}
		return ( new Bills\Retrieve() )
			->setConnection( $this->getConnection() )
			->setEntityId( $oBill->getId() )
			->sendRequestWithVoResponse();
	}
}