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

	/**
	 * This needs to be extended to add the Invoice Item details.
	 * @param string $sTxnID a Stripe Charge ID
	 * @return Freeagent\DataWrapper\ChargeVO
	 * @throws \Exception
	 */
	public function buildChargeFromTransaction( $sTxnID ) {
		$charge = new Freeagent\DataWrapper\ChargeVO();

		try {
			$details = $this->getTxnChargeDetails( $sTxnID );

			$charge->id = $sTxnID;
			$charge->currency = strtoupper( $details->GrossAmount->currencyID );
			$charge->date = strtotime( $details->PaymentDate );
			$charge->gateway = 'paypalexpress';
			$charge->payment_terms = $this->getFreeagentConfigVO()->invoice_payment_terms;
			$charge->setAmount_Gross( $details->GrossAmount->value )
				   ->setAmount_Fee( $details->FeeAmount->value )
				   ->setAmount_Net( $details->GrossAmount->value - $details->FeeAmount->value );
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
		$oPayout = new Freeagent\DataWrapper\PayoutVO();
		$oPayout->setId( $payoutID );

		try {
			$oDets = $this->getTxnChargeDetails( $payoutID );
			$oPayout->date_arrival = strtotime( $oDets->PaymentDate );
			$oPayout->currency = $oDets->GrossAmount->currencyID;

			$oPayout->addCharge(
				$this->buildChargeFromTransaction( $payoutID )
			);
		}
		catch ( \Exception $oE ) {
		}

		return $oPayout;
	}

	/**
	 * @param string $sTxnID
	 * @return \PayPal\EBLBaseComponents\PaymentInfoType
	 * @throws \Exception
	 */
	protected function getTxnChargeDetails( $sTxnID ) {
		$oReqType = new GetTransactionDetailsRequestType();
		$oReqType->TransactionID = $sTxnID;

		$oReq = new GetTransactionDetailsReq();
		$oReq->GetTransactionDetailsRequest = $oReqType;

		return $this->getPaypalMerchantApi()
					->api()
					->GetTransactionDetails( $oReq )->PaymentTransactionDetails->PaymentInfo;
	}
}