<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices;

use FernleafSystems\ApiWrappers\Freeagent\Entities\Invoices\InvoiceVO;
use FernleafSystems\Integrations\Freeagent\DataWrapper\ChargeVO;
use FernleafSystems\Utilities\Data\Adapter\DynPropertiesClass;

/**
 * @property InvoiceVO $external_invoice
 * @property ChargeVO  $charge
 */
class InvoicesPartsToReconcileVO extends DynPropertiesClass {

	/**
	 * @return InvoiceVO
	 * @deprecated
	 */
	public function getFreeagentInvoice() {
		return $this->external_invoice;
	}

	/**
	 * @return ChargeVO
	 * @deprecated
	 */
	public function getCharge() {
		return $this->charge;
	}

	/**
	 * @param InvoiceVO $oInvoice
	 * @return $this
	 */
	public function setFreeagentInvoice( $oInvoice ) {
		$this->external_invoice = $oInvoice;
		return $this;
	}

	/**
	 * @param ChargeVO $oBalTxn
	 * @return $this
	 */
	public function setChargeVo( $oBalTxn ) {
		$this->charge = $oBalTxn;
		return $this;
	}
}