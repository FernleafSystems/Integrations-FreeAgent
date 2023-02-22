<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers;
use FernleafSystems\Integrations\Freeagent\Reconciliation;

class ProcessPayout {

	use ConnectionConsumer;
	use Consumers\BridgeConsumer;
	use Consumers\FreeagentConfigVoConsumer;

	/**
	 * - verify we can load the bank account
	 * - verify we can load the bank transaction (maybe create it automatically if not)
	 * - reconcile stripe charges with freeagent invoices
	 * - reconcile stripe fees with freeagent bill
	 * @throws \Exception
	 */
	public function process( string $payoutID ) {
		$bridge = $this->getBridge();
		$faConn = $this->getConnection();
		$payout = $bridge->buildPayoutFromId( $payoutID );
		$faCfg = $this->getFreeagentConfigVO();

		$bankID = $faCfg->getBankAccountIdForCurrency( $payout->getCurrency() );
		if ( empty( $bankID ) ) {
			throw new \Exception( sprintf( 'No bank account specified for currency "%s".', $payout->getCurrency() ) );
		}

		$bankAccount = ( new Entities\BankAccounts\Retrieve() )
			->setConnection( $faConn )
			->setEntityId( $bankID )
			->retrieve();
		if ( empty( $bankAccount ) ) {
			throw new \Exception( sprintf( 'Could not retrieve bank account with ID "%s".', $bankID ) );
		}

		$txn = null;
		$bankTxnID = $bridge->getExternalBankTxnId( $payout );
		if ( !empty( $bankTxnID ) ) {
			$txn = ( new Entities\BankTransactions\Retrieve() )
				->setConnection( $faConn )
				->setEntityId( $bankTxnID )
				->retrieve();
			if ( $txn instanceof Entities\BankTransactions\BankTransactionVO
				 && $txn->amount != $payout->getTotalNet() ) {
				$txn = null; // useful if we're trying to correct something after the fact.
			}
		}

		// Find/Create the Freeagent Bank Transaction
		if ( empty( $txn ) && $faCfg->auto_locate_bank_txn ) {
			$txn = ( new Reconciliation\BankTransactions\FindForPayout() )
				->setConnection( $faConn )
				->setPayoutVO( $payout )
				->setBankAccountVo( $bankAccount )
				->find();
		}
		if ( empty( $txn ) && $faCfg->auto_create_bank_txn ) {
			$txn = ( new Reconciliation\BankTransactions\CreateForPayout() )
				->setConnection( $faConn )
				->setPayoutVO( $payout )
				->setBankAccountVo( $bankAccount )
				->create();
		}

		if ( empty( $txn ) ) {
			throw new \Exception( sprintf( 'Bank Transaction does not exist for this Payout "%s".', $payout->id ) );
		}

		$bridge->storeExternalBankTxnId( $payout, $txn );

		// 1) Reconcile all the Invoices
		( new Reconciliation\ProcessInvoicesForPayout() )
			->setConnection( $faConn )
			->setBridge( $bridge )
			->setFreeagentConfigVO( $faCfg )
			->setPayoutVO( $payout )
			->setBankTransactionVo( $txn )
			->run();

		// 2) Reconcile the Stripe Bill
		( new Reconciliation\ProcessBillForPayout() )
			->setConnection( $faConn )
			->setPayoutVO( $payout )
			->setFreeagentConfigVO( $faCfg )
			->setBankTransactionVo( $txn )
			->setBridge( $bridge )
			->run();
	}
}