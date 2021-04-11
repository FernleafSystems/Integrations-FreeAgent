<?php

namespace FernleafSystems\Integrations\Freeagent\Service\Stripe;

use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent;
use Stripe\{
	BalanceTransaction,
	Charge,
	PaymentIntent,
	Payout,
	Refund
};

abstract class StripeBridge extends Freeagent\Reconciliation\Bridge\StandardBridge {

	/**
	 * This needs to be extended to add the Invoice Item details.
	 * @param string $chargeId a Stripe Charge ID
	 * @return Freeagent\DataWrapper\ChargeVO
	 * @throws \Exception
	 */
	public function buildChargeFromTransaction( $chargeId ) {
		$charge = new Freeagent\DataWrapper\ChargeVO();

		$stripeCharge = Charge::retrieve( $chargeId );
		$balanceTXN = BalanceTransaction::retrieve( $stripeCharge->balance_transaction );

		$charge->id = $chargeId;
		$charge->currency = strtoupper( $stripeCharge->currency );
		$charge->date = $stripeCharge->created;
		$charge->gateway = 'stripe';
		$charge->payment_terms = $this->getFreeagentConfigVO()->invoice_payment_terms;
		return $charge->setAmount_Gross( bcdiv( $balanceTXN->amount, 100, 2 ) )
					   ->setAmount_Fee( bcdiv( $balanceTXN->fee, 100, 2 ) )
					   ->setAmount_Net( bcdiv( $balanceTXN->net, 100, 2 ) );
	}

	/**
	 * This needs to be extended to add the Invoice Item details.
	 * @param BalanceTransaction $balTxn a Stripe Refund ID
	 * @return Freeagent\DataWrapper\AdjustmentVO
	 * @throws \Exception
	 */
	public function buildAdjustmentFromBalTxn( BalanceTransaction $balTxn ) :Freeagent\DataWrapper\AdjustmentVO {

		$adj = new Freeagent\DataWrapper\AdjustmentVO();
		if ( strpos( $balTxn->source, 'du_' ) === 0 ) {
			$adj->type = 'dispute';
		}

		$adj->currency = $balTxn->currency;
		$adj->date = $balTxn->created;

		return $adj->setAmount_Gross( bcdiv( $balTxn->amount, 100, 2 ) )
				   ->setAmount_Fee( bcdiv( $balTxn->fee, 100, 2 ) )
				   ->setAmount_Net( bcdiv( $balTxn->net, 100, 2 ) );
	}

	/**
	 * This needs to be extended to add the Invoice Item details.
	 * @param string $refundID a Stripe Refund ID
	 * @return Freeagent\DataWrapper\RefundVO
	 * @throws \Exception
	 */
	public function buildRefundFromId( $refundID ) {
		$refund = new Freeagent\DataWrapper\RefundVO();

		$oStrRefund = Refund::retrieve( $refundID );
		$balanceTXN = BalanceTransaction::retrieve( $oStrRefund->balance_transaction );

		$refund->id = $refundID;
		$refund->currency = $oStrRefund->currency;
		$refund->date = $oStrRefund->created;
		$refund->gateway = 'stripe';
		return $refund->setAmount_Gross( bcdiv( $balanceTXN->amount, 100, 2 ) )
					  ->setAmount_Fee( bcdiv( $balanceTXN->fee, 100, 2 ) )
					  ->setAmount_Net( bcdiv( $balanceTXN->net, 100, 2 ) );
	}

	/**
	 * @param string $payoutID
	 * @return Freeagent\DataWrapper\PayoutVO
	 * @throws \Exception
	 */
	public function buildPayoutFromId( $payoutID ) {
		$payout = new Freeagent\DataWrapper\PayoutVO();
		$payout->setId( $payoutID );

		$stripePayout = Payout::retrieve( $payoutID );

		$nTotalPotentialDiff = 0;
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
				elseif ( $balTxn->type == 'payout_failure' ) {
					$nTotalPotentialDiff += $balTxn->net;
				}
			}
		}
		catch ( \Exception $oE ) {
			var_dump( $oE->getMessage() );
			error_log( $oE->getMessage() );
		}

		/**
		 * 2019-11
		 * We're handling here for failed payouts (due to TransferWise borderless account restrictions).
		 * In the case where a refund is issued and it results in a "negative payment" because we don't have
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
		$nTotalPayoutVO = bcsub(
			bcmul( $payout->getTotalGross(), 100, 0 ),
			bcmul( $payout->getTotalFee(), 100, 0 )
		);

		$nPayoutDiscrepancy = bcsub( $stripePayout->amount, $nTotalPayoutVO );
		if ( $nPayoutDiscrepancy != 0 && bccomp( abs( $nPayoutDiscrepancy ), abs( $nTotalPotentialDiff ) ) ) {
			throw new \Exception( sprintf( 'PayoutVO total (%s) differs from Stripe total (%s). Discrepancy: %s',
				$nTotalPayoutVO, $stripePayout->amount, $nPayoutDiscrepancy ) );
		}

		$payout->date_arrival = $stripePayout->arrival_date;
		$payout->currency = $stripePayout->currency;
		return $payout;
	}

	/**
	 * @param Payout $oStripePayout
	 * @return BalanceTransaction[]
	 */
	protected function getStripeBalanceTransactions( $oStripePayout ) {
		try {
			$aBalTxns = ( new Utility\GetStripeBalanceTransactionsFromPayout() )
				->setStripePayout( $oStripePayout )
				->retrieve();
		}
		catch ( \Exception $oE ) {
			$aBalTxns = [];
		}
		return $aBalTxns;
	}

	/**
	 * @param Freeagent\DataWrapper\PayoutVO $oPayoutVO
	 * @return int|null
	 */
	public function getExternalBankTxnId( $oPayoutVO ) {
		return Payout::retrieve( $oPayoutVO->id )->metadata[ 'ext_bank_txn_id' ];
	}

	/**
	 * @param Freeagent\DataWrapper\PayoutVO $oPayoutVO
	 * @return int|null
	 */
	public function getExternalBillId( $oPayoutVO ) {
		return Payout::retrieve( $oPayoutVO->id )->metadata[ 'ext_bill_id' ];
	}

	/**
	 * @param Freeagent\DataWrapper\PayoutVO              $oPayoutVO
	 * @param Entities\BankTransactions\BankTransactionVO $oBankTxn
	 * @return $this
	 */
	public function storeExternalBankTxnId( $oPayoutVO, $oBankTxn ) {
		$oStripePayout = Payout::retrieve( $oPayoutVO->id );
		$oStripePayout->metadata[ 'ext_bank_txn_id' ] = $oBankTxn->getId();
		$oStripePayout->save();
		return $this;
	}

	/**
	 * @param Freeagent\DataWrapper\PayoutVO $oPayoutVO
	 * @param Entities\Bills\BillVO          $oBill
	 * @return $this
	 */
	public function storeExternalBillId( $oPayoutVO, $oBill ) {
		$oStripePayout = Payout::retrieve( $oPayoutVO->id );
		$oStripePayout->metadata[ 'ext_bill_id' ] = $oBill->getId();
		$oStripePayout->save();
		return $this;
	}
}