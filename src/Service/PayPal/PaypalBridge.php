<?php

namespace FernleafSystems\Integrations\Freeagent\Service\PayPal;

use FernleafSystems\Integrations\Freeagent;
use FernleafSystems\Integrations\Freeagent\Service\PayPal;
use PayPal\PayPalAPI\{
	GetTransactionDetailsReq,
	GetTransactionDetailsRequestType
};

abstract class PaypalBridge extends Freeagent\Reconciliation\Bridge\StandardBridge {

	use PayPal\Consumers\PaypalMerchantApiConsumer;

	public const GATEWAY_SLUG = 'paypalexpress';

	/**
	 * This needs to be extended to add the Invoice Item details.
	 * @param string $chargeId a Stripe Charge ID
	 * @return Freeagent\DataWrapper\ChargeVO
	 * @throws \Exception
	 */
	public function buildChargeFromTransaction( $chargeId ) {
		$charge = new Freeagent\DataWrapper\ChargeVO();

		try {
			$txn = $this->getTxnChargeDetails( $chargeId );

			$charge->id = $chargeId;
			$charge->currency = strtoupper( $txn->currency );
			$charge->date = strtotime( $txn->time );
			$charge->gateway = static::GATEWAY_SLUG;
			$charge->payment_terms = $this->getFreeagentConfigVO()->invoice_payment_terms;
			$charge->amount_gross = $txn->gross_value;
			$charge->amount_fee = $txn->fee_value;
			$charge->amount_net = $txn->net_value;
		}
		catch ( \Exception $e ) {
		}

		return $charge;
	}

	/**
	 * This isn't applicable to PayPal
	 * @param string $refundID
	 * @return Freeagent\DataWrapper\RefundVO
	 */
	public function buildRefundFromId( $refundID ) {
		return null;
	}

	/**
	 * With Paypal, the Transaction and the Payout are essentially the same thing.
	 * @param string $payoutID
	 * @return Freeagent\DataWrapper\PayoutVO
	 */
	public function buildPayoutFromId( $payoutID ) {
		$payout = new Freeagent\DataWrapper\PayoutVO();
		$payout->setId( $payoutID );

		try {
			$txn = $this->getTxnChargeDetails( $payoutID );
			$payout->date_arrival = strtotime( $txn->time );
			$payout->currency = $txn->currency;

			$payout->addCharge(
				$this->buildChargeFromTransaction( $payoutID )
			);
		}
		catch ( \Exception $e ) {
		}

		return $payout;
	}

	/**
	 * @param string $txnID
	 * @return TransactionVO
	 * @throws \Exception
	 */
	protected function getTxnChargeDetails( $txnID ) {
		return $this->getTxnChargeDetailsLegacy( $txnID );
	}

	/**
	 * @param string $txnID
	 * @return TransactionVO
	 * @throws \Exception
	 */
	protected function getTxnChargeDetailsLegacy( $txnID ) {
		$reqType = new GetTransactionDetailsRequestType();
		$reqType->TransactionID = $txnID;

		$req = new GetTransactionDetailsReq();
		$req->GetTransactionDetailsRequest = $reqType;

		$ppTxn = $this->getPaypalMerchantApi()
					  ->api()
					  ->GetTransactionDetails( $req )->PaymentTransactionDetails->PaymentInfo;

		$txn = new TransactionVO();
		$txn->id = $txnID;
		$txn->status = $ppTxn->PaymentStatus;
		$txn->time = $ppTxn->PaymentDate;
		$txn->amount_with_breakdown = [
			'gross_amount' => [
				'currency_code' => $ppTxn->GrossAmount->currencyID,
				'value'         => $ppTxn->GrossAmount->value
			],
			'fee_amount'   => [
				'currency_code' => $ppTxn->FeeAmount->currencyID,
				'value'         => $ppTxn->FeeAmount->value
			],
			'net_amount'   => [
				'currency_code' => $ppTxn->GrossAmount->currencyID,
				'value'         => bcsub( $ppTxn->GrossAmount->value, $ppTxn->FeeAmount->value, 2 )
			],
		];

		return $txn;
	}
}