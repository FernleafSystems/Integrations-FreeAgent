<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bills;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities\BankTransactionExplanation;
use FernleafSystems\ApiWrappers\Freeagent\Entities\BankTransactions;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Bills;
use FernleafSystems\Integrations\Freeagent\Consumers;

class ExplainBankTxnWithForeignBill {

	use ConnectionConsumer;
	use Consumers\BankAccountVoConsumer;
	use Consumers\BankTransactionVoConsumer;
	use Consumers\PayoutVoConsumer;

	/**
	 * @throws \Exception
	 */
	public function createExplanation( Bills\BillVO $bill ) :bool {
		$bankXferExplanation = $this->createAccountTransferExplanation( $bill );
		$linkedTxn = $this->getNewLinkedBankTransferTransaction( $bankXferExplanation );
		$updatedBill = $this->updateBillWithNewValue( $bill, $linkedTxn->amount );
		$this->createBillExplanation( $updatedBill );
		return true;
	}

	/**
	 * @throws \Exception
	 */
	protected function createBillExplanation( Bills\BillVO $bill ) :BankTransactionExplanation\BankTransactionExplanationVO {
		$exp = ( new BankTransactionExplanation\CreateManual() )
			->setConnection( $this->getConnection() )
			->setBankAccount( $this->getBankAccountVo() )
			->setBillPaid( $bill )
			->setValue( $bill->total_value )
			->setDatedOn( $bill->dated_on )
			->create();
		if ( empty( $exp ) ) {
			throw new \Exception( 'Creation of final foreign bill for Stripe failed' );
		}
		return $exp;
	}

	/**
	 * @throws \Exception
	 */
	protected function createAccountTransferExplanation( Bills\BillVO $bill ) :BankTransactionExplanation\BankTransactionExplanationVO {

		$expl = ( new BankTransactionExplanation\CreateTransferToAnotherAccount() )
			->setConnection( $this->getConnection() )
			->setBankTxn( $this->getBankTransactionVo() )
			->setTargetBankAccount( $this->getBankAccountVo() )
			->setValue( -1*$bill->total_value )// -1 as it's leaving the account
			->create();
		if ( empty( $expl ) ) {
			throw new \Exception( 'Failed to explain bank transfer transaction in FreeAgent.' );
		}

		return $expl;
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
	 * @param float $value
	 * @throws \Exception
	 */
	protected function updateBillWithNewValue( Bills\BillVO $bill, $value ) :Bills\BillVO {
		$bill = ( new Bills\Update() )
			->setConnection( $this->getConnection() )
			->setTotalValue( $value )
			->setEntityId( $bill->getId() )
			->update();
		if ( empty( $bill ) ) {
			throw new \Exception( 'Failed to update Bill with new total value' );
		}
		return ( new Bills\Retrieve() )
			->setConnection( $this->getConnection() )
			->setEntityId( $bill->getId() )
			->sendRequestWithVoResponse();
	}
}