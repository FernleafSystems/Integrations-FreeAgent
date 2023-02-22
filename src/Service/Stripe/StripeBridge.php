<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Service\Stripe;

use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\ApiWrappers\Freeagent\Entities\BankTransactions\BankTransactionVO;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Bills\BillVO;
use FernleafSystems\Integrations\Freeagent;
use Stripe\{
	BalanceTransaction,
	Charge,
	PaymentIntent,
	Payout,
	Refund
};
use FernleafSystems\Integrations\Freeagent\DataWrapper\{
	AdjustmentVO,
	ChargeVO,
	PayoutVO,
	RefundVO
};

abstract class StripeBridge extends Freeagent\Reconciliation\Bridge\StandardBridge {

	/**
	 * This needs to be extended to add the Invoice Item details.
	 * @throws \Exception
	 */
	public function buildChargeFromTransaction( string $gatewayChargeID ) :ChargeVO {
		$charge = new ChargeVO();

		$stripeCharge = Charge::retrieve( $gatewayChargeID );
		$balanceTXN = BalanceTransaction::retrieve( $stripeCharge->balance_transaction );

		$charge->id = $gatewayChargeID;
		$charge->currency = strtoupper( $stripeCharge->currency );
		$charge->date = $stripeCharge->created;
		$charge->gateway = 'stripe';
		$charge->payment_terms = $this->getFreeagentConfigVO()->invoice_payment_terms;
		$charge->amount_gross = bcdiv( (string)$balanceTXN->amount, '100', 2 );
		$charge->amount_fee = bcdiv( (string)$balanceTXN->fee, '100', 2 );
		$charge->amount_net = bcdiv( (string)$balanceTXN->net, '100', 2 );
		return $charge;
	}

	/**
	 * This needs to be extended to add the Invoice Item details.
	 * @throws \Exception
	 */
	public function buildAdjustmentFromBalTxn( BalanceTransaction $balanceTXN ) :AdjustmentVO {

		$adj = new AdjustmentVO();
		if ( strpos( $balanceTXN->source, 'du_' ) === 0 ) {
			$adj->type = 'dispute';
		}

		$adj->currency = $balanceTXN->currency;
		$adj->date = $balanceTXN->created;
		$adj->amount_gross = bcdiv( (string)$balanceTXN->amount, '100', 2 );
		$adj->amount_fee = bcdiv( (string)$balanceTXN->fee, '100', 2 );
		$adj->amount_net = bcdiv( (string)$balanceTXN->net, '100', 2 );
		return $adj;
	}

	/**
	 * This needs to be extended to add the Invoice Item details.
	 * @throws \Exception
	 */
	public function buildRefundFromId( string $gatewayRefundID ) :?RefundVO {

		$stripeRefund = Refund::retrieve( $gatewayRefundID );
		$balanceTXN = BalanceTransaction::retrieve( $stripeRefund->balance_transaction );

		$refund = new RefundVO();
		$refund->id = $gatewayRefundID;
		$refund->currency = $stripeRefund->currency;
		$refund->date = $stripeRefund->created;
		$refund->gateway = 'stripe';
		$refund->amount_gross = bcdiv( (string)$balanceTXN->amount, '100', 2 );
		$refund->amount_fee = bcdiv( (string)$balanceTXN->fee, '100', 2 );
		$refund->amount_net = bcdiv( (string)$balanceTXN->net, '100', 2 );
		return $refund;
	}

	/**
	 * @throws \Exception
	 */
	public function buildPayoutFromId( string $payoutID ) :PayoutVO {
		$payout = new PayoutVO();
		$payout->setId( $payoutID );

		$stripePayout = Payout::retrieve( $payoutID );

		$totalPotentialDiff = 0;
		try {
			foreach ( $this->getStripeBalanceTransactions( $stripePayout ) as $balTxn ) {
				if ( $balTxn->type == 'charge' ) {
					$payout->addCharge( $this->buildChargeFromTransaction( $balTxn->source ) );
				}
				elseif ( $balTxn->type == 'refund' ) {
					if ( strpos( $balTxn->source, 'ch_' ) === 0 ) {
						$PI = PaymentIntent::retrieve(
							Charge::retrieve( $balTxn->source )->payment_intent
						);
						foreach ( $PI->charges as $ch ) {
							/** @var Charge $ch */
							foreach ( $ch->refunds as $oRefund ) {
								/** @var Refund $oRefund */
								$payout->addRefund( $this->buildRefundFromId( $oRefund->id ) );
							}
						}
					}
					else {
						$payout->addRefund( $this->buildRefundFromId( $balTxn->source ) );
					}
				}
				elseif ( $balTxn->type == 'adjustment' ) {
					$payout->addAdjustment( $this->buildAdjustmentFromBalTxn( $balTxn ) );
				}
				elseif ( in_array( $balTxn->type, [ 'payout_failure', 'transfer_failure' ] ) ) {
					$totalPotentialDiff += $balTxn->net;
				}
			}
		}
		catch ( \Exception $e ) {
			var_dump( $e->getMessage() );
			error_log( $e->getMessage() );
		}

		/**
		 * 2019-11
		 * We're handling here for failed payouts (due to TransferWise borderless account restrictions).
		 * In the case where a refund is issued, and it results in a "negative payment" because we don't have
		 * sufficient funds within Stripe to cover it, it results in a "payout_failure" because TransferWise
		 * doesn't support withdrawals.
		 *
		 * So the subsequent Payout will have a "payout_failure" balance transaction within it, making the
		 * totals out-of-sync so we cater to this scenario below by allowing a totals discrepancy, but only
		 * as far as the total "payout_failures"
		 *
		 * In 9999/10000 cases, $nPayoutTotalDifference should be ZERO.
		 *
		 * 2020-05-13
		 * - Changed from getTotalNet() to getTotalGross() because Stripe stopped refunding fees.
		 * - We then.
		 */
		$totalPayoutVO = bcsub(
			bcmul( $payout->getTotalGross(), '100', 0 ),
			bcmul( $payout->getTotalFee(), '100', 0 )
		);

		$payoutDiscrepancy = (int)bcsub( (string)$stripePayout->amount, $totalPayoutVO );
		if ( $payoutDiscrepancy !== 0
			 && bccomp( (string)abs( $payoutDiscrepancy ), (string)abs( $totalPotentialDiff ) ) ) {
			throw new \Exception( sprintf( 'PayoutVO total (%s) differs from Stripe total (%s). Discrepancy: %s',
				$totalPayoutVO, $stripePayout->amount, $payoutDiscrepancy ) );
		}

		$payout->date_arrival = $stripePayout->arrival_date;
		$payout->currency = $stripePayout->currency;
		return $payout;
	}

	/**
	 * @return BalanceTransaction[]
	 */
	protected function getStripeBalanceTransactions( Payout $stripePayout ) :array {
		try {
			$txn = ( new Utility\GetStripeBalanceTransactionsFromPayout() )
				->setStripePayout( $stripePayout )
				->retrieve();
		}
		catch ( \Exception $e ) {
			$txn = [];
		}
		return $txn;
	}

	public function getExternalBankTxnId( PayoutVO $payout ) :?string {
		return (string)Payout::retrieve( $payout->id )->metadata[ 'ext_bank_txn_id' ] ?? null;
	}

	public function getExternalBillId( PayoutVO $payout ) :?string {
		return (string)Payout::retrieve( $payout->id )->metadata[ 'ext_bill_id' ] ?? null;
	}

	public function storeExternalBankTxnId( PayoutVO $payout, BankTransactionVO $bankTxn ) :self {
		$stripePayout = Payout::retrieve( $payout->id );
		$stripePayout->metadata[ 'ext_bank_txn_id' ] = $bankTxn->getId();
		$stripePayout->save();
		return $this;
	}

	public function storeExternalBillId( PayoutVO $payout, BillVO $bill ) :self {
		$stripePayout = Payout::retrieve( $payout->id );
		$stripePayout->metadata[ 'ext_bill_id' ] = $bill->getId();
		$stripePayout->save();
		return $this;
	}
}