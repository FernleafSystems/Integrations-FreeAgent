<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers;
use FernleafSystems\Integrations\Freeagent\Lookups\CurrencyExchangeRates;

class ExplainBankTxnWithInvoices {

	use ConnectionConsumer;
	use Consumers\BankTransactionVoConsumer;
	use Consumers\BridgeConsumer;
	use Consumers\PayoutVoConsumer;

	/**
	 * @param InvoicesPartsToReconcileVO[] $aInvoicesToReconcile
	 */
	public function run( $aInvoicesToReconcile ) {
		$conn = $this->getConnection();
		$oPayout = $this->getPayoutVO();

		$sBaseCurrency = $this->getBaseCurrency();
		$sPayoutDatedOn = date( 'Y-m-d', $this->getPayoutVO()->date_arrival );
		$oCurrencyEx = new CurrencyExchangeRates();

		$bankTxn = $this->getBankTransactionVo();
		/** @var Entities\BankAccounts\BankAccountVO $oBankAccount */
		foreach ( $aInvoicesToReconcile as $oInvoiceItem ) {

			$invoice = $oInvoiceItem->external_invoice;
			$oCharge = $oInvoiceItem->charge;

			if ( (int)$invoice->due_value == 0 ) {
				continue;
			}

			try {
				$oCreator = ( new Entities\BankTransactionExplanation\Create() )
					->setConnection( $conn )
					->setBankTxn( $bankTxn )
					->setInvoicePaid( $invoice )
					->setDatedOn( $sPayoutDatedOn )// also consider: $invoice->getDatedOn()
					->setValue( (string)$invoice->total_value );

				$sChargeCurrency = $oCharge->currency;
				// e.g. we're explaining a USD invoice using a transaction in GBP bank account
				if ( strcasecmp( $sChargeCurrency, $oPayout->getCurrency() ) != 0 ) { //foreign currency converted by Stripe
					$oCreator->setForeignCurrencyValue( $invoice->total_value );
				}
				else {
					// We do some optimisation with unrealised currency gains/losses.
					try {
						$nInvoiceDateRate = $oCurrencyEx->lookup( $sBaseCurrency, $sChargeCurrency, $invoice->dated_on );
						$nPayoutDateRate = $oCurrencyEx->lookup( $sBaseCurrency, $sChargeCurrency, $sPayoutDatedOn );

						// if the target currency got stronger we'd have unrealised gains, so we negate
						// them by changing the invoice creation date to be when we received the payout.
						// TODO: Further investigate this and whether it's just shifting the gains
						// and losses to the date of the invoice.
						if ( false && $nInvoiceDateRate > $nPayoutDateRate ) {
							( new Entities\Invoices\MarkAs() )
								->setConnection( $conn )
								->setEntityId( $invoice->getId() )
								->draft();
							sleep( 1 );
							$invoice = ( new Entities\Invoices\Update() )
								->setConnection( $conn )
								->setEntityId( $invoice->getId() )
								->setDatedOn( $sPayoutDatedOn )
								->update();
							sleep( 1 );
							( new Entities\Invoices\MarkAs() )
								->setConnection( $conn )
								->setEntityId( $invoice->getId() )
								->sent();
						}
					}
					catch ( \Exception $oE ) {
					}
				}

				$oCreator->create();
			}
			catch ( \Exception $oE ) {
				continue;
			}
			//Store some meta in Payment / Charge?
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
}