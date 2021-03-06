<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers;
use FernleafSystems\Integrations\Freeagent\Reconciliation;

/**
 * Class ProcessStripePayout
 * @package FernleafSystems\Integrations\Freeagent\Reconciliation
 */
class ProcessPayout {

	use Consumers\BridgeConsumer,
		Consumers\FreeagentConfigVoConsumer,
		ConnectionConsumer;

	/**
	 * - verify we can load the bank account
	 * - verify we can load the bank transaction (maybe create it automatically if not)
	 * - reconcile stripe charges with freeagent invoices
	 * - reconcile stripe fees with freeagent bill
	 * @param string $sPayoutId
	 * @throws \Exception
	 */
	public function process( $sPayoutId ) {
		$oBridge = $this->getBridge();
		$oCon = $this->getConnection();
		$oPayout = $oBridge->buildPayoutFromId( $sPayoutId );
		$oFreeagentConfig = $this->getFreeagentConfigVO();

		$sBankId = $oFreeagentConfig->getBankAccountIdForCurrency( $oPayout->getCurrency() );
		if ( empty( $sBankId ) ) {
			throw new \Exception( sprintf( 'No bank account specified for currency "%s".', $oPayout->getCurrency() ) );
		}

		$oBankAccount = ( new Entities\BankAccounts\Retrieve() )
			->setConnection( $oCon )
			->setEntityId( $sBankId )
			->retrieve();
		if ( empty( $oBankAccount ) ) {
			throw new \Exception( sprintf( 'Could not retrieve bank account with ID "%s".', $sBankId ) );
		}

		$oBankTxn = null;
		$nBankTxnId = $oBridge->getExternalBankTxnId( $oPayout );
		if ( !empty( $nBankTxnId ) ) {
			$oBankTxn = ( new Entities\BankTransactions\Retrieve() )
				->setConnection( $oCon )
				->setEntityId( $nBankTxnId )
				->retrieve();
			if ( $oBankTxn instanceof Entities\BankTransactions\BankTransactionVO
				 && $oBankTxn->amount != $oPayout->getTotalNet() ) {
				$oBankTxn = null; // useful if we're trying to correct something after the fact.
			}
		}

		// Find/Create the Freeagent Bank Transaction
		if ( empty( $oBankTxn ) && $oFreeagentConfig->auto_locate_bank_txn ) {
			$oBankTxn = ( new Reconciliation\BankTransactions\FindForPayout() )
				->setConnection( $oCon )
				->setPayoutVO( $oPayout )
				->setBankAccountVo( $oBankAccount )
				->find();
		}
		if ( empty( $oBankTxn ) && $oFreeagentConfig->auto_create_bank_txn ) {
			$oBankTxn = ( new Reconciliation\BankTransactions\CreateForPayout() )
				->setConnection( $oCon )
				->setPayoutVO( $oPayout )
				->setBankAccountVo( $oBankAccount )
				->create();
		}

		if ( empty( $oBankTxn ) ) {
			throw new \Exception( sprintf( 'Bank Transaction does not exist for this Payout "%s".', $oPayout->id ) );
		}

		$oBridge->storeExternalBankTxnId( $oPayout, $oBankTxn );

		// 1) Reconcile all the Invoices
		( new Reconciliation\ProcessInvoicesForPayout() )
			->setConnection( $oCon )
			->setBridge( $oBridge )
			->setFreeagentConfigVO( $oFreeagentConfig )
			->setPayoutVO( $oPayout )
			->setBankTransactionVo( $oBankTxn )
			->run();

		// 2) Reconcile the Stripe Bill
		( new Reconciliation\ProcessBillForPayout() )
			->setConnection( $oCon )
			->setPayoutVO( $oPayout )
			->setFreeagentConfigVO( $oFreeagentConfig )
			->setBankTransactionVo( $oBankTxn )
			->setBridge( $oBridge )
			->run();
	}
}