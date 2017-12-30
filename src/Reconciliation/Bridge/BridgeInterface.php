<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bridge;

use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\DataWrapper;

interface BridgeInterface {

	/**
	 * @param string $sTxnID
	 * @return DataWrapper\ChargeVO
	 */
	public function buildChargeFromTransaction( $sTxnID );

	/**
	 * @param string $sPayoutId
	 * @return DataWrapper\PayoutVO
	 */
	public function buildPayoutFromId( $sPayoutId );

	/**
	 * @param DataWrapper\ChargeVO $oCharge
	 * @param bool                 $bUpdateOnly
	 * @return Entities\Contacts\ContactVO
	 */
	public function createFreeagentContact( $oCharge, $bUpdateOnly = false );

	/**
	 * @param DataWrapper\ChargeVO $oCharge
	 * @return Entities\Invoices\InvoiceVO
	 */
	public function createFreeagentInvoice( $oCharge );

	/**
	 * @param DataWrapper\ChargeVO $oCharge
	 * @return int
	 */
	public function getFreeagentContactId( $oCharge );

	/**
	 * @param DataWrapper\ChargeVO $oCharge
	 * @return int
	 */
	public function getFreeagentInvoiceId( $oCharge );

	/**
	 * @param DataWrapper\ChargeVO $oCharge
	 * @return bool
	 */
	public function verifyInternalPaymentLink( $oCharge );
}