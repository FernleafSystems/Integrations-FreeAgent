<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers;

class ExplainBankTxnWithInvoices {

	use ConnectionConsumer;
	use Consumers\BankTransactionVoConsumer;
	use Consumers\BridgeConsumer;
	use Consumers\PayoutVoConsumer;

	/**
	 * @param InvoicesPartsToReconcileVO[] $invoices
	 */
	public function run( array $invoices ) {
		$conn = $this->getConnection();
		$payout = $this->getPayoutVO();

		$bankTxn = $this->getBankTransactionVo();
		/** @var Entities\BankAccounts\BankAccountVO $oBankAccount */
		foreach ( $invoices as $invoiceItem ) {

			$invoice = $invoiceItem->external_invoice;
			$charge = $invoiceItem->charge;

			if ( (int)$invoice->due_value == 0 ) {
				continue;
			}

			try {
				$creator = new Entities\BankTransactionExplanation\Create();
				$creator->setBankTxn( $bankTxn )
						->setInvoicePaid( $invoice )
						->setDatedOn( $bankTxn->dated_on )
						->setValue( (string)$invoice->total_value )
						->setConnection( $conn );

				$currencyCharge = $charge->currency;
				// e.g. we're explaining a USD invoice using a transaction in GBP bank account
				if ( strcasecmp( $currencyCharge, $payout->getCurrency() ) != 0 ) { //foreign currency converted by Stripe
					$creator->setForeignCurrencyValue( $invoice->total_value );
				}

				$creator->create();
			}
			catch ( \Exception $e ) {
				error_log( $e->getMessage() );
				continue;
			}
			//Store some meta in Payment / Charge?
		}
	}
}