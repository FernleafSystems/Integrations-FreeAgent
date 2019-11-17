<?php

namespace FernleafSystems\Integrations\Freeagent\Service\Stripe;

use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent;
use Stripe\{
	BalanceTransaction,
	Charge,
	Payout,
	Refund
};

abstract class StripeBridge extends Freeagent\Reconciliation\Bridge\StandardBridge {

	/**
	 * This needs to be extended to add the Invoice Item details.
	 * @param string $sChargeId a Stripe Charge ID
	 * @return Freeagent\DataWrapper\ChargeVO
	 * @throws \Exception
	 */
	public function buildChargeFromTransaction( $sChargeId ) {
		$oCharge = new Freeagent\DataWrapper\ChargeVO();

		$oStripeCharge = Charge::retrieve( $sChargeId );
		$oBalTxn = BalanceTransaction::retrieve( $oStripeCharge->balance_transaction );

		$oCharge->id = $sChargeId;
		$oCharge->currency = strtoupper( $oStripeCharge->currency );
		$oCharge->date = $oStripeCharge->created;
		$oCharge->gateway = 'stripe';
		$oCharge->payment_terms = $this->getFreeagentConfigVO()->invoice_payment_terms;
		return $oCharge->setAmount_Gross( bcdiv( $oBalTxn->amount, 100, 2 ) )
					   ->setAmount_Fee( bcdiv( $oBalTxn->fee, 100, 2 ) )
					   ->setAmount_Net( bcdiv( $oBalTxn->net, 100, 2 ) );
	}

	/**
	 * This needs to be extended to add the Invoice Item details.
	 * @param string $sRefundId a Stripe Refund ID
	 * @return Freeagent\DataWrapper\RefundVO
	 * @throws \Exception
	 */
	public function buildRefundFromId( $sRefundId ) {
		$oRefund = new Freeagent\DataWrapper\RefundVO();

		$oStrRefund = Refund::retrieve( $sRefundId );
		$oBalTxn = BalanceTransaction::retrieve( $oStrRefund->balance_transaction );

		$oRefund->id = $sRefundId;
		$oRefund->currency = $oStrRefund->currency;
		$oRefund->date = $oStrRefund->created;
		$oRefund->gateway = 'stripe';
		return $oRefund->setAmount_Gross( bcdiv( $oBalTxn->amount, 100, 2 ) )
					   ->setAmount_Fee( bcdiv( $oBalTxn->fee, 100, 2 ) )
					   ->setAmount_Net( bcdiv( $oBalTxn->net, 100, 2 ) );
	}

	/**
	 * @param string $sPayoutId
	 * @return Freeagent\DataWrapper\PayoutVO
	 * @throws \Exception
	 */
	public function buildPayoutFromId( $sPayoutId ) {
		$oPayout = new Freeagent\DataWrapper\PayoutVO();
		$oPayout->setId( $sPayoutId );

		$oStripePayout = Payout::retrieve( $sPayoutId );
		try {
			foreach ( $this->getStripeBalanceTransactions( $oStripePayout ) as $oBalTxn ) {
				if ( $oBalTxn->type == 'charge' ) {
					$oPayout->addCharge( $this->buildChargeFromTransaction( $oBalTxn->source ) );
				}
				else if ( $oBalTxn->type == 'refund' ) {
					$oPayout->addRefund( $this->buildRefundFromId( $oBalTxn->source ) );
				}
			}
		}
		catch ( \Exception $oE ) {
		}

		$nCompareTotal = bcmul( $oPayout->getTotalNet(), 100, 0 );
		if ( bccomp( $oStripePayout->amount, $nCompareTotal ) ) {
			throw new \Exception( sprintf( 'PayoutVO total (%s) differs from Stripe total (%s)',
				$nCompareTotal, $oStripePayout->amount ) );
		}

		$oPayout->date_arrival = $oStripePayout->arrival_date;
		$oPayout->currency = $oStripePayout->currency;
		return $oPayout;
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