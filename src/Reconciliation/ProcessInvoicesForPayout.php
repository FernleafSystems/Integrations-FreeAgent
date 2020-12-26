<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers;
use FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices;

/**
 * Verifies all invoices associated with the payout are present and accurate within Freeagent
 * Then reconciles all local invoices/Stripe Charges with the exported invoices within Freeagent
 * Class StripeChargesWithFreeagentTransaction
 * @package iControlWP\Integration\FreeAgent\Reconciliation
 */
class ProcessInvoicesForPayout {

	use ConnectionConsumer;
	use Consumers\BankTransactionVoConsumer;
	use Consumers\BridgeConsumer;
	use Consumers\FreeagentConfigVoConsumer;
	use Consumers\PayoutVoConsumer;

	/**
	 * @throws \Exception
	 */
	public function run() {

		$aReconInvoiceData = ( new Invoices\InvoicesVerify() )
			->setConnection( $this->getConnection() )
			->setBridge( $this->getBridge() )
			->setFreeagentConfigVO( $this->getFreeagentConfigVO() )
			->setPayoutVO( $this->getPayoutVO() )
			->run();

		( new Invoices\ExplainBankTxnWithInvoices() )
			->setConnection( $this->getConnection() )
			->setPayoutVO( $this->getPayoutVO() )
			->setBridge( $this->getBridge() )
			->setBankTransactionVo( $this->getBankTransactionVo() )
			->run( $aReconInvoiceData );
	}
}