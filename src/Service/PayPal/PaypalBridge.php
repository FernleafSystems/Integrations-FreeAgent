<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Service\PayPal;

use FernleafSystems\Integrations\Freeagent\DataWrapper\{
	ChargeVO,
	PayoutVO,
	RefundVO
};
use FernleafSystems\Integrations\Freeagent\Reconciliation\Bridge\StandardBridge;
use PayPal\PayPalAPI\{
	GetTransactionDetailsReq,
	GetTransactionDetailsRequestType
};
use PaypalServerSdkLib\Models\Money;
use PaypalServerSdkLib\Models\TransactionDetails;

abstract class PaypalBridge extends StandardBridge {

	use Consumers\PaypalMerchantApiConsumer;
	use Consumers\PaypalRestApiConsumer;

	public const GATEWAY_SLUG = 'paypalexpress';

	protected bool $useRestApi = false;

	protected const TXN_SEARCH_DAYS_BACK = 90;

	public function setUseRestApi( bool $use ): self {
		$this->useRestApi = $use;
		return $this;
	}

	/**
	 * This needs to be extended to add the Invoice Item details.
	 */
	public function buildChargeFromTransaction( string $gatewayChargeID ): ChargeVO {
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
		catch ( \Exception ) {
		}

		return $charge;
	}

	/**
	 * This isn't applicable to PayPal
	 */
	public function buildRefundFromId( string $gatewayRefundID ): ?RefundVO {
		return null;
	}

	/**
	 * With Paypal, the Transaction and the Payout are essentially the same thing.
	 */
	public function buildPayoutFromId( string $payoutID ): PayoutVO {
		$payout = new PayoutVO();
		$payout->setId( $payoutID );

		try {
			$txn = $this->getTxnChargeDetails( $payoutID );
			$payout->date_arrival = \strtotime( $txn->time );
			$payout->currency = $txn->currency;
			$payout->addCharge( $this->buildChargeFromTransaction( $payoutID ) );
		}
		catch ( \Exception ) {
		}

		return $payout;
	}

	/**
	 * @throws \Exception
	 */
	protected function getTxnChargeDetails( string $txnID ): TransactionVO {
		return $this->useRestApi
			? $this->getTxnChargeDetailsRest( $txnID )
			: $this->getTxnChargeDetailsLegacy( $txnID );
	}

	/**
	 * Legacy NVP/SOAP implementation
	 * @throws \Exception
	 */
	protected function getTxnChargeDetailsLegacy( string $txnID ): TransactionVO {
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

	/**
	 * New REST API implementation
	 * @throws \Exception
	 */
	protected function getTxnChargeDetailsRest( string $txnID ): TransactionVO {
		$controller = $this->getPaypalRestApi()->api()->getTransactionSearchController();

		$endDate = ( new \DateTime( 'now', new \DateTimeZone( 'UTC' ) ) )->format( 'Y-m-d\TH:i:s\Z' );
		$startDate = ( new \DateTime( '-' . static::TXN_SEARCH_DAYS_BACK . ' days', new \DateTimeZone( 'UTC' ) ) )->format( 'Y-m-d\TH:i:s\Z' );

		$response = $controller->searchTransactions( [
			'startDate'     => $startDate,
			'endDate'       => $endDate,
			'transactionId' => $txnID,
			'fields'        => 'transaction_info',
			'pageSize'      => 1,
		] );

		/** @var TransactionDetails[]|null $transactions */
		$transactions = $response->getResult()->getTransactionDetails();
		if ( empty( $transactions ) ) {
			throw new \Exception( "Transaction $txnID not found" );
		}

		$txnInfo = $transactions[ 0 ]->getTransactionInfo();
		if ( $txnInfo === null ) {
			throw new \Exception( "Transaction info not available for $txnID" );
		}
		$txn = new TransactionVO();
		$txn->id = $txnID;
		$txn->status = $this->mapRestStatus( $txnInfo->getTransactionStatus() );
		$txn->time = $txnInfo->getTransactionInitiationDate();

		/** @var ?Money $amt */
		$amt = $txnInfo->getTransactionAmount();
		$fee = $txnInfo->getFeeAmount();
		$grossValue = $amt?->getValue() ?? '0.00';
		$feeValue = $fee?->getValue() ?? '0.00';

		$txn->amount_with_breakdown = [
			'gross_amount' => [
				'currency_code' => $amt?->getCurrencyCode() ?? 'USD',
				'value'         => $grossValue
			],
			'fee_amount'   => [
				'currency_code' => $fee?->getCurrencyCode() ?? 'USD',
				'value'         => $feeValue
			],
			'net_amount'   => [
				'currency_code' => $amt?->getCurrencyCode() ?? 'USD',
				'value'         => \bcsub( $grossValue, $feeValue, 2 )
			],
		];

		return $txn;
	}

	protected function mapRestStatus( ?string $code ): string {
		return match ( $code ) {
			'S'     => 'Completed',
			'P'     => 'Pending',
			'D'     => 'Denied',
			'V'     => 'Reversed',
			default => $code ?? 'Unknown'
		};
	}
}
