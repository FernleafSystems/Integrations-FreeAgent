<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bridge;

use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\DataWrapper;

interface BridgeInterface {

	const KEY_FREEAGENT_INVOICE_IDS = 'freeagent_invoice_ids';

	/**
	 * @param string $sChargeId
	 * @return DataWrapper\ChargeVO
	 */
	public function buildChargeFromTransaction( $sChargeId );

	/**
	 * @param string $sRefundId
	 * @return DataWrapper\RefundVO
	 */
	public function buildRefundFromId( $sRefundId );

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
	 * @param DataWrapper\PayoutVO $oPayout
	 * @return int|null
	 */
	public function getExternalBankTxnId( $oPayout );

	/**
	 * @param DataWrapper\PayoutVO $oPayout
	 * @return int|null
	 */
	public function getExternalBillId( $oPayout );

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
	 * @param DataWrapper\ChargeVO        $oCharge
	 * @param Entities\Invoices\InvoiceVO $oInvoice
	 * @return $this
	 */
	public function storeFreeagentInvoiceIdForCharge( $oCharge, $oInvoice );

	/**
	 * @param DataWrapper\PayoutVO                        $oPayout
	 * @param Entities\BankTransactions\BankTransactionVO $oBankTxn
	 * @return $this
	 */
	public function storeExternalBankTxnId( $oPayout, $oBankTxn );

	/**
	 * @param DataWrapper\PayoutVO  $oPayout
	 * @param Entities\Bills\BillVO $oBill
	 * @return $this
	 */
	public function storeExternalBillId( $oPayout, $oBill );

	/**
	 * @param DataWrapper\ChargeVO $oCharge
	 * @return bool
	 */
	public function verifyInternalPaymentLink( $oCharge );
}