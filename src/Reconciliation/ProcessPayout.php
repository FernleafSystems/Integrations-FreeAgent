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

	use ConnectionConsumer;
	use Consumers\BridgeConsumer;
	use Consumers\FreeagentConfigVoConsumer;

	/**
	 * - verify we can load the bank account
	 * - verify we can load the bank transaction (maybe create it automatically if not)
	 * - reconcile stripe charges with freeagent invoices
	 * - reconcile stripe fees with freeagent bill
	 * @param string $sPayoutId
	 * @throws \Exception
	 */
	public function process( $sPayoutId ) {
		$bridge = $this->getBridge();
		$conn = $this->getConnection();
		$oPayout = $bridge->buildPayoutFromId( $sPayoutId );
		$faCfg = $this->getFreeagentConfigVO();

		$sBankId = $faCfg->getBankAccountIdForCurrency( $oPayout->getCurrency() );
		if ( empty( $sBankId ) ) {
			throw new \Exception( sprintf( 'No bank account specified for currency "%s".', $oPayout->getCurrency() ) );
		}

		$oBankAccount = ( new Entities\BankAccounts\Retrieve() )
			->setConnection( $conn )
			->setEntityId( $sBankId )
			->retrieve();
		if ( empty( $oBankAccount ) ) {
			throw new \Exception( sprintf( 'Could not retrieve bank account with ID "%s".', $sBankId ) );
		}

		$txn = null;
		$nBankTxnId = $bridge->getExternalBankTxnId( $oPayout );
		if ( !empty( $nBankTxnId ) ) {
			$txn = ( new Entities\BankTransactions\Retrieve() )
				->setConnection( $conn )
				->setEntityId( $nBankTxnId )
				->retrieve();
			if ( $txn instanceof Entities\BankTransactions\BankTransactionVO
				 && $txn->amount != $oPayout->getTotalNet() ) {
				$txn = null; // useful if we're trying to correct something after the fact.
			}
		}

		// Find/Create the Freeagent Bank Transaction
		if ( empty( $txn ) && $faCfg->auto_locate_bank_txn ) {
			$txn = ( new Reconciliation\BankTransactions\FindForPayout() )
				->setConnection( $conn )
				->setPayoutVO( $oPayout )
				->setBankAccountVo( $oBankAccount )
				->find();
		}
		if ( empty( $txn ) && $faCfg->auto_create_bank_txn ) {
			$txn = ( new Reconciliation\BankTransactions\CreateForPayout() )
				->setConnection( $conn )
				->setPayoutVO( $oPayout )
				->setBankAccountVo( $oBankAccount )
				->create();
		}

		if ( empty( $txn ) ) {
			throw new \Exception( sprintf( 'Bank Transaction does not exist for this Payout "%s".', $oPayout->id ) );
		}

		$bridge->storeExternalBankTxnId( $oPayout, $txn );

		// 1) Reconcile all the Invoices
		( new Reconciliation\ProcessInvoicesForPayout() )
			->setConnection( $conn )
			->setBridge( $bridge )
			->setFreeagentConfigVO( $faCfg )
			->setPayoutVO( $oPayout )
			->setBankTransactionVo( $txn )
			->run();

		// 2) Reconcile the Stripe Bill
		( new Reconciliation\ProcessBillForPayout() )
			->setConnection( $conn )
			->setPayoutVO( $oPayout )
			->setFreeagentConfigVO( $faCfg )
			->setBankTransactionVo( $txn )
			->setBridge( $bridge )
			->run();
	}
}