<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bills;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers;

/**
 * Retrieve the Stripe Bill within FreeAgent, and the associated Bank Transaction
 * for the Payout and creates a FreeAgent Explanation for it.
 */
class ExplainBankTxnWithStripeBill {

	use ConnectionConsumer;
	use Consumers\BankTransactionVoConsumer;
	use Consumers\FreeagentConfigVoConsumer;
	use Consumers\PayoutVoConsumer;

	/**
	 * Determine whether we're working in our native currency, or whether
	 * we have to explain the bill using our Foreign Bill handling.
	 * @param Entities\Bills\BillVO $bill
	 * @throws \Exception
	 */
	public function process( $bill ) {
		if ( $bill instanceof Entities\Bills\BillVO && $bill->due_value > 0 ) {
			$PO = $this->getPayoutVO();

			$bUseForeignCurrencyBill = $this->getFreeagentConfigVO()->foreign_currency_bills
									   || ( strcasecmp( $PO->currency, $this->getBaseCurrency() ) == 0 );
			if ( $bUseForeignCurrencyBill ) {
				$this->createSimpleExplanation( $bill );
			}
			else {
				// Uses a dedicated bank account as an intermediary for managing foreign currency bills
				$foreignCurrencyAccount = $this->getForeignCurrencyBankAccount();
				if ( is_null( $foreignCurrencyAccount ) ) {
					throw  new \Exception( 'Attempting to explain a foreign currency bill without a currency transfer account.' );
				}

				( new ExplainBankTxnWithForeignBill() )
					->setPayoutVO( $this->getPayoutVO() )
					->setConnection( $this->getConnection() )
					->setBankTransactionVo( $this->getBankTransactionVo() )
					->setBankAccountVo( $foreignCurrencyAccount )
					->createExplanation( $bill );
			}
		}
	}

	/**
	 * @throws \Exception
	 */
	public function createSimpleExplanation( Entities\Bills\BillVO $bill ) {

		$oBankTxnExp = ( new Entities\BankTransactionExplanation\Create() )
			->setConnection( $this->getConnection() )
			->setBankTxn( $this->getBankTransactionVo() )
			->setBillPaid( $bill )
			->setValue( $bill->total_value )
			->create();

		if ( empty( $oBankTxnExp ) ) {
			throw new \Exception( 'Failed to explain bank transaction with a bill in FreeAgent.' );
		}
	}

	/**
	 * @return string
	 */
	protected function getBaseCurrency() {
		return ( new Entities\Company\Retrieve() )
			->setConnection( $this->getConnection() )
			->retrieve()
			->currency;
	}

	/**
	 * @return Entities\BankAccounts\BankAccountVO|null
	 */
	protected function getForeignCurrencyBankAccount() {
		$oForeignBankAccount = null;

		$nForeignBankAccountId = $this->getFreeagentConfigVO()->bank_account_id_foreign;
		if ( !empty( $nForeignBankAccountId ) ) { // we retrieve it even though it may not be needed
			$oForeignBankAccount = ( new Entities\BankAccounts\Retrieve() )
				->setConnection( $this->getConnection() )
				->setEntityId( $nForeignBankAccountId )
				->retrieve();
		}
		return $oForeignBankAccount;
	}
}