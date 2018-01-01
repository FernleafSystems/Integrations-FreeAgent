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
abstract class ProcessPayout {

	use Consumers\BridgeConsumer,
		ConnectionConsumer,
		Consumers\FreeagentConfigVoConsumer,
		Consumers\PayoutVoConsumer;

	/**
	 * - verify we can load the bank account
	 * - verify we can load the bank transaction (maybe create it automatically if not)
	 * - reconcile stripe charges with freeagent invoices
	 * - reconcile stripe fees with freeagent bill
	 * @throws \Exception
	 */
	public function process() {
		$oCon = $this->getConnection();
		$oPayout = $this->getPayoutVO();
		$oFreeagentConfig = $this->getFreeagentConfigVO();

		$sBankId = $oFreeagentConfig->getBankAccountIdForCurrency( $oPayout->getCurrency() );
		if ( empty( $sBankId ) ) {
			throw new \Exception( sprintf( 'No bank account specified for currency "%s".', $oPayout->getCurrency() ) );
		}

		/** @var Entities\BankAccounts\BankAccountVO $oBankAccount */
		$oBankAccount = ( new Entities\BankAccounts\Retrieve() )
			->setConnection( $oCon )
			->setEntityId( $sBankId )
			->sendRequestWithVoResponse();
		if ( empty( $oBankAccount ) ) {
			throw new \Exception( sprintf( 'Could not retrieve bank account with ID "%s".', $sBankId ) );
		}

		// Find/Create the Freeagent Bank Transaction
		$oBankTxn = ( new Reconciliation\BankTransactions\FindForPayout() )
			->setConnection( $oCon )
			->setPayoutVO( $oPayout )
			->setBankAccountVo( $oBankAccount )
			->find();
		if ( empty( $oBankTxn ) ) {
			if ( $oFreeagentConfig->isAutoCreateBankTransactions() ) {
				$oBankTxn = ( new Reconciliation\BankTransactions\CreateForPayout() )
					->setConnection( $oCon )
					->setPayoutVO( $oPayout )
					->setBankAccountVo( $oBankAccount )
					->create();
			}
		}
		if ( empty( $oBankTxn ) ) {
			throw new \Exception( sprintf( 'Bank Transaction does not exist for this Payout "%s".', $oPayout->id ) );
		}

		// 1) Reconcile all the Invoices
		( new Reconciliation\ProcessInvoicesForPayout() )
			->setConnection( $oCon )
			->setPayoutVO( $oPayout )
			->setBankTransactionVo( $oBankTxn )
			->setBridge( $this->getBridge() )
			->run();

		// 2) Reconcile the Stripe Bill
		( new Reconciliation\ProcessBillForPayout() )
			->setConnection( $oCon )
			->setPayoutVO( $oPayout )
			->setFreeagentConfigVO( $oFreeagentConfig )
			->setBankTransactionVo( $oBankTxn )
			->run();
	}
}