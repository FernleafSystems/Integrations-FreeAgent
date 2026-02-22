<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bills;

use FernleafSystems\ApiWrappers\Freeagent\Entities\BankAccounts;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Bills;
use FernleafSystems\ApiWrappers\Freeagent\Entities\BankTransactionExplanation;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Company;
use FernleafSystems\Integrations\Freeagent\Consumers\BankTransactionVoConsumer;

/**
 * Retrieve the Stripe Bill within FreeAgent, and the associated Bank Transaction
 * for the Payout and creates a FreeAgent Explanation for it.
 */
class ExplainBankTxnWithBill extends BillsBase {

	use BankTransactionVoConsumer;

	/**
	 * Determine whether we're working in our native currency, or whether
	 * we have to explain the bill using our Foreign Bill handling.
	 * @throws \Exception
	 */
	public function process( Bills\BillVO $bill ) :void {
		if ( $bill->due_value > 0 ) {
			$PO = $this->getPayoutVO();

			$useForeignCurrencyBill = $this->getFreeagentConfigVO()->foreign_currency_bills
									  || ( \strcasecmp( $PO->currency, $this->getBaseCurrency() ) == 0 );
			if ( $useForeignCurrencyBill ) {
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
					->setBankTransactionVo( $this->getBankTransactionVo() )
					->setBankAccountVo( $foreignCurrencyAccount )
					->setConnection( $this->getConnection() )
					->createExplanation( $bill );
			}
		}
	}

	/**
	 * @throws \Exception
	 */
	public function createSimpleExplanation( Bills\BillVO $bill ) :void {

		$explanation = ( new BankTransactionExplanation\Create() )
			->setBankTxn( $this->getBankTransactionVo() )
			->setBillPaid( $bill )
			->setValue( ( $bill->total_value > 0 ? -1 : 1 )*$bill->total_value )
			->setCategory( $this->getBillCategory()->url )
			->setConnection( $this->getConnection() )
			->create();

		if ( empty( $explanation ) ) {
			throw new \Exception( 'Failed to explain bank transaction with a bill in FreeAgent.' );
		}
	}

	protected function getBaseCurrency() :string {
		return (string)( new Company\Retrieve() )
			->setConnection( $this->getConnection() )
			->retrieve()
			->currency;
	}

	protected function getForeignCurrencyBankAccount() :?BankAccounts\BankAccountVO {
		$foreignBankAccount = null;

		$bankAccountId = $this->getFreeagentConfigVO()->bank_account_id_foreign;
		if ( !empty( $bankAccountId ) ) { // we retrieve it even though it may not be needed
			$foreignBankAccount = ( new BankAccounts\Retrieve() )
				->setEntityId( $bankAccountId )
				->setConnection( $this->getConnection() )
				->retrieve();
		}
		return $foreignBankAccount;
	}
}