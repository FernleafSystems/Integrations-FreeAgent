<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices;

use FernleafSystems\ApiWrappers\Freeagent\Entities\Invoices\InvoiceVO;
use FernleafSystems\Integrations\Freeagent\DataWrapper\ChargeVO;
use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

class InvoicesPartsToReconcileVO {

	use StdClassAdapter;

	/**
	 * @return InvoiceVO
	 */
	public function getFreeagentInvoice() {
		return $this->getParam( 'external_invoice' );
	}

	/**
	 * @return ChargeVO
	 */
	public function getCharge() {
		return $this->getParam( 'charge' );
	}

	/**
	 * @param InvoiceVO $oInvoice
	 * @return $this
	 */
	public function setFreeagentInvoice( $oInvoice ) {
		return $this->setParam( 'external_invoice', $oInvoice );
	}

	/**
	 * @param ChargeVO $oBalTxn
	 * @return $this
	 */
	public function setChargeVo( $oBalTxn ) {
		return $this->setParam( 'charge', $oBalTxn );
	}
}