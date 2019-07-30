<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers;
use FernleafSystems\Integrations\Freeagent\Lookups\CurrencyExchangeRates;

class ExplainBankTxnWithInvoices {

	use ConnectionConsumer,
		Consumers\BankTransactionVoConsumer,
		Consumers\BridgeConsumer,
		Consumers\PayoutVoConsumer;

	/**
	 * @param InvoicesPartsToReconcileVO[] $aInvoicesToReconcile
	 */
	public function run( $aInvoicesToReconcile ) {
		$oConn = $this->getConnection();
		$oPayout = $this->getPayoutVO();

		$sBaseCurrency = $this->getBaseCurrency();
		$sPayoutDatedOn = date( 'Y-m-d', $this->getPayoutVO()->date_arrival );
		$oCurrencyEx = new CurrencyExchangeRates();

		$oBankTxn = $this->getBankTransactionVo();
		/** @var Entities\BankAccounts\BankAccountVO $oBankAccount */
		foreach ( $aInvoicesToReconcile as $oInvoiceItem ) {

			$oInvoice = $oInvoiceItem->external_invoice;
			$oCharge = $oInvoiceItem->charge;

			if ( (int)$oInvoice->due_value == 0 ) {
				continue;
			}

			try {
				$oCreator = ( new Entities\BankTransactionExplanation\Create() )
					->setConnection( $oConn )
					->setBankTxn( $oBankTxn )
					->setInvoicePaid( $oInvoice )
					->setDatedOn( $sPayoutDatedOn )// also consider: $oInvoice->getDatedOn()
					->setValue( (string)$oInvoice->total_value );

				$sChargeCurrency = $oCharge->getCurrency();
				// e.g. we're explaining a USD invoice using a transaction in GBP bank account
				if ( strcasecmp( $sChargeCurrency, $oPayout->getCurrency() ) != 0 ) { //foreign currency converted by Stripe
					$oCreator->setForeignCurrencyValue( $oInvoice->total_value );
				}
				else {
					// We do some optimisation with unrealised currency gains/losses.
					try {
						$nInvoiceDateRate = $oCurrencyEx->lookup( $sBaseCurrency, $sChargeCurrency, $oInvoice->dated_on );
						$nPayoutDateRate = $oCurrencyEx->lookup( $sBaseCurrency, $sChargeCurrency, $sPayoutDatedOn );

						// if the target currency got stronger we'd have unrealised gains, so we negate
						// them by changing the invoice creation date to be when we received the payout.
						// TODO: Further investigate this and whether it's just shifting the gains
						// and losses to the date of the invoice.
						if ( false && $nInvoiceDateRate > $nPayoutDateRate ) {
							( new Entities\Invoices\MarkAs() )
								->setConnection( $oConn )
								->setEntityId( $oInvoice->getId() )
								->draft();
							sleep( 1 );
							$oInvoice = ( new Entities\Invoices\Update() )
								->setConnection( $oConn )
								->setEntityId( $oInvoice->getId() )
								->setDatedOn( $sPayoutDatedOn )
								->update();
							sleep( 1 );
							( new Entities\Invoices\MarkAs() )
								->setConnection( $oConn )
								->setEntityId( $oInvoice->getId() )
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