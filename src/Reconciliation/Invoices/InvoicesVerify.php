<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers\BridgeConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\FreeagentConfigVoConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\PayoutVoConsumer;

class InvoicesVerify {

	use BridgeConsumer,
		ConnectionConsumer,
		FreeagentConfigVoConsumer,
		PayoutVoConsumer;

	/**
	 * @var Entities\Invoices\InvoiceVO[]
	 */
	private $aFreeagentInvoices;

	/**
	 * Will return a collection of all invoices to be reconciled, or null if there
	 * was a problem during the verification process.
	 * @return InvoicesPartsToReconcileVO[]
	 * @throws \Exception
	 */
	public function run() {

		$oBridge = $this->getBridge();
		$oPayout = $this->getPayoutVO();

		$aFreeagentInvoicesPool = $this->getFreeagentInvoicesPool();

		$oInvoiceCreator = ( new CreateFromCharge() )
			->setBridge( $this->getBridge() )
			->setConnection( $this->getConnection() )
			->setFreeagentConfigVO( $this->getFreeagentConfigVO() );

		// Verify FreeAgent Invoice exists for each Stripe Balance Transaction
		// that is represented in the Payout.
		$nTxnCount = 0;
		$aInvoicesToReconcile = array();
		foreach ( $oPayout->getCharges() as $oCharge ) {

			$oInvoiceToReconcile = null;

			// We first check that we can build the link reliably between this ($oBalTxn)
			// Stripe Balance Transaction, and the internal Payment (which links us to Freeagent)
			$bValidLink = $oBridge->verifyInternalPaymentLink( $oCharge );
			if ( !$bValidLink ) {
				continue;
			}

			$nFreeagentInvoiceId = $oBridge->getFreeagentInvoiceId( $oCharge );
			if ( !empty( $nFreeagentInvoiceId ) ) {
				// Verify we've been able to load it.
				foreach ( $aFreeagentInvoicesPool as $oInvoice ) {
					if ( $nFreeagentInvoiceId == $oInvoice->getId() ) {
						$oInvoiceToReconcile = $oInvoice;
						break;
					}
				}
			}

			if ( is_null( $oInvoiceToReconcile ) ) { // No Invoice, so we create it.
				$oNewInvoice = $oInvoiceCreator->setChargeVO( $oCharge )
											   ->create();
				if ( !empty( $oNewInvoice ) ) {
					$oInvoiceToReconcile = $oNewInvoice;
				}
			}

			if ( !is_null( $oInvoiceToReconcile ) ) {
				$aInvoicesToReconcile[] = ( new InvoicesPartsToReconcileVO() )
					->setFreeagentInvoice( $oInvoiceToReconcile )
					->setChargeVo( $oCharge );
			}

			$nTxnCount++;
		}

		if ( count( $aInvoicesToReconcile ) != $nTxnCount ) {
			throw new \Exception( 'The number of invoices to reconcile does not equal the Stripe TXN count.' );
		}

		return $aInvoicesToReconcile;
	}

	/**
	 * These are the collection of invoices which we'll use to find the
	 * corresponding invoice to Stripe Transaction
	 * @return Entities\Invoices\InvoiceVO[]
	 */
	protected function getFreeagentInvoicesPool() {
		if ( !isset( $this->aFreeagentInvoices ) ) {
			$this->aFreeagentInvoices = ( new Entities\Invoices\Find() )
				->setConnection( $this->getConnection() )
				->filterByOpenOverdue()
				->filterByLastXMonths( 1 )
				->all();
		}
		return $this->aFreeagentInvoices;
	}
}