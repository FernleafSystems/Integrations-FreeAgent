<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Service\PayPal;

use FernleafSystems\Integrations\Freeagent\DataWrapper\{
	ChargeVO,
	PayoutVO,
	RefundVO
};
use FernleafSystems\Integrations\Freeagent\Reconciliation\Bridge\StandardBridge;
use FernleafSystems\Integrations\Freeagent\Service\PayPal;
use PayPal\PayPalAPI\{
	GetTransactionDetailsReq,
	GetTransactionDetailsRequestType
};

abstract class PaypalBridge extends StandardBridge {

	use PayPal\Consumers\PaypalMerchantApiConsumer;

	public const GATEWAY_SLUG = 'paypalexpress';

	/**
	 * This needs to be extended to add the Invoice Item details.
	 */
	public function buildChargeFromTransaction( string $gatewayChargeID ) :ChargeVO {
		$charge = new ChargeVO();

		try {
			$txn = $this->getTxnChargeDetails( $gatewayChargeID );

			$charge->id = $gatewayChargeID;
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
	 */
	public function buildRefundFromId( string $gatewayRefundID ) :?RefundVO {
		return null;
	}

	/**
	 * With Paypal, the Transaction and the Payout are essentially the same thing.
	 */
	public function buildPayoutFromId( string $payoutID ) :PayoutVO {
		$payout = new PayoutVO();
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
	 * @throws \Exception
	 */
	protected function getTxnChargeDetails( string $txnID ) :TransactionVO {
		return $this->getTxnChargeDetailsLegacy( $txnID );
	}

	/**
	 * @throws \Exception
	 */
	protected function getTxnChargeDetailsLegacy( string $txnID ) :TransactionVO {
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