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
		$oCharge = new Freeagent\DataWrapper\ChargeVO();

		try {
			$oDets = $this->getTxnChargeDetails( $sTxnID );

			$oCharge->id = $sTxnID;
			$oCharge->currency = strtoupper( $oDets->GrossAmount->currencyID );
			$oCharge->date = strtotime( $oDets->PaymentDate );
			$oCharge->gateway = 'paypalexpress';
			$oCharge->payment_terms = $this->getFreeagentConfigVO()->invoice_payment_terms;
			$oCharge->setAmount_Gross( $oDets->GrossAmount->value )
					->setAmount_Fee( $oDets->FeeAmount->value )
					->setAmount_Net( $oDets->GrossAmount->value - $oDets->FeeAmount->value );
		}
		catch ( \Exception $oE ) {
		}

		return $oCharge;
	}

	/**
	 * This isn't applicable to PayPal
	 * @param string $sRefundId
	 * @return Freeagent\DataWrapper\RefundVO
	 */
	public function buildRefundFromId( $sRefundId ) {
		return null;
	}

	/**
	 * With Paypal, the Transaction and the Payout are essentially the same thing.
	 * @param string $sPayoutId
	 * @return Freeagent\DataWrapper\PayoutVO
	 */
	public function buildPayoutFromId( $sPayoutId ) {
		$oPayout = new Freeagent\DataWrapper\PayoutVO();
		$oPayout->setId( $sPayoutId );

		try {
			$oDets = $this->getTxnChargeDetails( $sPayoutId );
			$oPayout->date_arrival = strtotime( $oDets->PaymentDate );
			$oPayout->currency = $oDets->GrossAmount->currencyID;

			$oPayout->addCharge(
				$this->buildChargeFromTransaction( $sPayoutId )
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