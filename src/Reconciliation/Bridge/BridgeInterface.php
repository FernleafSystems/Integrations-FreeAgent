<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bridge;

use FernleafSystems\ApiWrappers\Freeagent\Entities\{
	BankTransactions\BankTransactionVO,
	Bills\BillVO,
	Contacts\ContactVO,
	Invoices\InvoiceVO
};
use FernleafSystems\Integrations\Freeagent\DataWrapper\{
	ChargeVO,
	PayoutVO,
	RefundVO
};

interface BridgeInterface {

	public const KEY_FREEAGENT_INVOICE_IDS = 'freeagent_invoice_ids';

	public function buildChargeFromTransaction( string $gatewayChargeID ) :ChargeVO;

	public function buildRefundFromId( string $gatewayRefundID ) :?RefundVO;

	public function buildPayoutFromId( string $payoutID ) :PayoutVO;

	public function createFreeagentContact( ChargeVO $charge, bool $updateOnly = false ) :?ContactVO;

	public function getExternalBankTxnId( PayoutVO $payout ) :?string;

	public function getExternalBillId( PayoutVO $payout ) :?string;

	public function getFreeagentContactId( ChargeVO $charge ) :?int;

	public function getFreeagentInvoiceId( ChargeVO $charge ) :?int;

	public function storeFreeagentInvoiceIdForCharge( ChargeVO $charge, InvoiceVO $invoice ) :self;

	public function storeExternalBankTxnId( PayoutVO $payout, BankTransactionVO $bankTxn ) :self;

	public function storeExternalBillId( PayoutVO $payout, BillVO $bill ) :self;

	public function verifyInternalPaymentLink( ChargeVO $charge ) :bool;
}