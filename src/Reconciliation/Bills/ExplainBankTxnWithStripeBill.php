<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bills;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers;

/**
 * Retrieve the Stripe Bill within FreeAgent, and the associated Bank Transaction
 * for the Payout and creates a FreeAgent Explanation for it.
 * Class ExplainBankTxnWithStripeBill
 * @package FernleafSystems\Integrations\Freeagent\Reconciliation\Bills
 */
class ExplainBankTxnWithStripeBill {

	use ConnectionConsumer;
	use Consumers\BankTransactionVoConsumer;
	use Consumers\FreeagentConfigVoConsumer;
	use Consumers\PayoutVoConsumer;

	/**
	 * Determine whether we're working in our native currency, or whether
	 * we have to explain the bill using our Foreign Bill handling.
	 * @param Entities\Bills\BillVO $oBill
	 * @throws \Exception
	 */
	public function process( $oBill ) {
		if ( $oBill->due_value > 0 ) {
			$oPO = $this->getPayoutVO();

			$bUseForeignCurrencyBill = $this->getFreeagentConfigVO()->foreign_currency_bills
									   || ( strcasecmp( $oPO->currency, $this->getBaseCurrency() ) == 0 );
			if ( $bUseForeignCurrencyBill ) {
				$this->createSimpleExplanation( $oBill );
			}
			else {
				// Uses a dedicated bank account as an intermediary for managing foreign currency bills
				$oForeignBankAccount = $this->getForeignCurrencyBankAccount();
				if ( is_null( $oForeignBankAccount ) ) {
					throw  new \Exception( 'Attempting to explain a foreign currency bill without a currency transfer account.' );
				}

				( new ExplainBankTxnWithForeignBill() )
					->setPayoutVO( $this->getPayoutVO() )
					->setConnection( $this->getConnection() )
					->setBankTransactionVo( $this->getBankTransactionVo() )
					->setBankAccountVo( $oForeignBankAccount )
					->createExplanation( $oBill );
			}
		}
	}

	/**
	 * @param Entities\Bills\BillVO $bill
	 * @throws \Exception
	 */
	public function createSimpleExplanation( $bill ) {

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