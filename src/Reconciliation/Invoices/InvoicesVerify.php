<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers\BridgeConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\FreeagentConfigVoConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\PayoutVoConsumer;

class InvoicesVerify {

	use BridgeConsumer;
	use ConnectionConsumer;
	use FreeagentConfigVoConsumer;
	use PayoutVoConsumer;

	/**
	 * @var Entities\Invoices\InvoiceVO[]
	 */
	private ?array $freeagentInvoices = null;

	/**
	 * Will return a collection of all invoices to be reconciled, or null if there
	 * was a problem during the verification process.
	 * @return InvoicesPartsToReconcileVO[]
	 * @throws \Exception
	 */
	public function run() :array {
		$bridge = $this->getBridge();

		$creator = ( new CreateFromCharge() )
			->setBridge( $this->getBridge() )
			->setConnection( $this->getConnection() )
			->setFreeagentConfigVO( $this->getFreeagentConfigVO() );

		// Verify FreeAgent Invoice exists for each Stripe Balance Transaction
		// that is represented in the Payout.
		$txnCount = 0;
		$invoicesToReconcile = [];
		foreach ( $this->getPayoutVO()->charges as $charge ) {

			$invoiceToReconcile = null;

			// We first check that we can build the link reliably between this ($oBalTxn)
			// Stripe Balance Transaction, and the internal Payment (which links us to Freeagent)
			if ( !$bridge->verifyInternalPaymentLink( $charge ) ) {
				continue;
			}

			$freeagentInvoiceID = $bridge->getFreeagentInvoiceId( $charge );
			if ( !empty( $freeagentInvoiceID ) ) {
				// Verify we've been able to load it.
				foreach ( $this->getFreeagentInvoicesPool() as $invoice ) {
					if ( $freeagentInvoiceID == $invoice->getId() ) {
						$invoiceToReconcile = $invoice;
						break;
					}
				}
			}

			if ( is_null( $invoiceToReconcile ) ) { // No Invoice, so we create it.
				$newInvoice = $creator->setChargeVO( $charge )->create();
				if ( !empty( $newInvoice ) ) {
					$invoiceToReconcile = $newInvoice;
				}
			}

			if ( !is_null( $invoiceToReconcile ) ) {
				$invoicePartToReconcile = new InvoicesPartsToReconcileVO();
				$invoicePartToReconcile->external_invoice = $invoiceToReconcile;
				$invoicePartToReconcile->charge = $charge;
				$invoicesToReconcile[] = $invoicePartToReconcile;
			}

			$txnCount++;
		}

		if ( count( $invoicesToReconcile ) != $txnCount ) {
			throw new \Exception( 'The number of invoices to reconcile does not equal the Stripe TXN count.' );
		}

		return $invoicesToReconcile;
	}

	/**
	 * These are the collection of invoices which we'll use to find the
	 * corresponding invoice to Stripe Transaction
	 * @return Entities\Invoices\InvoiceVO[]
	 */
	protected function getFreeagentInvoicesPool() :array {
		if ( !is_array( $this->freeagentInvoices ) ) {
			$this->freeagentInvoices = [];

			$invoicesIT = new Entities\Invoices\InvoicesIterator();
			$invoicesIT->setConnection( $this->getConnection() )
					   ->filterByOpenOverdue()
					   ->filterByLastXMonths(); // 1 month
			foreach ( $invoicesIT as $invoice ) {
				$this->freeagentInvoices[] = $invoice;
			}
		}
		return $this->freeagentInvoices;
	}
}