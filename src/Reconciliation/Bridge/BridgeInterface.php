<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bridge;

use FernleafSystems\ApiWrappers\Freeagent\Entities\Contacts\ContactVO;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Invoices\InvoiceVO;
use FernleafSystems\Integrations\Freeagent\DataWrapper\ChargeVO;

interface BridgeInterface {

	/**
	 * @param ChargeVO $oCharge
	 * @param bool     $bUpdateOnly
	 * @return ContactVO
	 */
	public function createFreeagentContact( $oCharge, $bUpdateOnly = false );

	/**
	 * @param ChargeVO $oCharge
	 * @return InvoiceVO
	 */
	public function createFreeagentInvoice( $oCharge );

	/**
	 * @param ChargeVO $oCharge
	 * @return int
	 */
	public function getFreeagentContactId( $oCharge );

	/**
	 * @param ChargeVO $oCharge
	 * @return int
	 */
	public function getFreeagentInvoiceId( $oCharge );

	/**
	 * @param ChargeVO $oCharge
	 * @return bool
	 */
	public function verifyInternalPaymentLink( $oCharge );
}