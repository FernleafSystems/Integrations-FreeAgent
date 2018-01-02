<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\BankTransactionVoConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\BridgeConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\FreeagentConfigVoConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\PayoutVoConsumer;
use FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices\ExplainBankTxnWithInvoices;
use FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices\InvoicesVerify;

/**
 * Verifies all invoices associated with the payout are present and accurate within Freeagent
 * Then reconciles all local invoices/Stripe Charges with the exported invoices within Freeagent
 * Class StripeChargesWithFreeagentTransaction
 * @package iControlWP\Integration\FreeAgent\Reconciliation
 */
class ProcessInvoicesForPayout {

	use BankTransactionVoConsumer,
		BridgeConsumer,
		ConnectionConsumer,
		FreeagentConfigVoConsumer,
		PayoutVoConsumer;

	/**
	 * @throws \Exception
	 */
	public function run() {

		$aReconInvoiceData = ( new InvoicesVerify() )
			->setConnection( $this->getConnection() )
			->setBridge( $this->getBridge() )
			->setFreeagentConfigVO( $this->getFreeagentConfigVO() )
			->setPayoutVO( $this->getPayoutVO() )
			->run();

		( new ExplainBankTxnWithInvoices() )
			->setConnection( $this->getConnection() )
			->setPayoutVO( $this->getPayoutVO() )
			->setBridge( $this->getBridge() )
			->setBankTransactionVo( $this->getBankTransactionVo() )
			->run( $aReconInvoiceData );
	}
}