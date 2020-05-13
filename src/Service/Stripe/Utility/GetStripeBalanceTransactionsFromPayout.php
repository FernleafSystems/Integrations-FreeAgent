<?php

namespace FernleafSystems\Integrations\Freeagent\Service\Stripe\Utility;

use FernleafSystems\Integrations\Freeagent\Service\Stripe;
use Stripe\{
	BalanceTransaction,
	Charge,
	Collection,
	Refund
};

/**
 * Class GetStripeBalanceTransactionsFromPayout
 * @package FernleafSystems\Integrations\Freeagent\Service\Stripe\Utility
 */
class GetStripeBalanceTransactionsFromPayout {

	use Stripe\Consumers\StripePayoutConsumer;

	/**
	 * @return BalanceTransaction[]
	 * @throws \Exception
	 */
	public function retrieve() {
		$oPO = $this->getStripePayout();
		/** @var BalanceTransaction[] $aChargeTxns */
		$aChargeTxns = [];
		/** @var BalanceTransaction[] $aRefundedCharges */
		$aRefundedCharges = [];
		/** @var BalanceTransaction[] $aPayoutFailures */
		$aPayoutFailures = [];

		$nTotalTally = 0;
		/** @var BalanceTransaction $oBalTxn */
		foreach ( $this->getPayoutBalanceTransactions()->autoPagingIterator() as $oBalTxn ) {

			$bIncludeInTotalTally = true;

			switch ( $oBalTxn->type ) {
				case 'charge':
					$aChargeTxns[] = $oBalTxn;
					break;
				case 'refund':
					$aRefundedCharges[] = $oBalTxn;
					break;
				case 'payout_failure':
					$aPayoutFailures[] = $oBalTxn;
					break;

				case 'payout':
				default:
					$bIncludeInTotalTally = false;
					break;
			}

			if ( $bIncludeInTotalTally ) {
				$nTotalTally += $oBalTxn->net;
			}
		}

		if ( $nTotalTally != $oPO->amount ) {
			throw new \Exception( sprintf( 'Total tally %s does not match transfer amount %s',
				$nTotalTally, $oPO->amount ) );
		}

		/**
		 * 2019-11
		 * With a quirk in TransferWise USD bank accounts, a (Reverse) Payout Failure occurred
		 * where they tried to take a payment but it fails. This leaves Freeagent in an inconsistent
		 * state.
		 * It's covered by the $aPayoutFailures here so we completely ignore the total in here
		 * that appears in subsequent Payouts that automatically adjust for the previously failed
		 * reverse Payout.
		 */

		/**
		 * 2020-05
		 * Stripe stopped refunding their fees. So we scrapped all that was here to handle refunds.
		 */

		return array_values( array_merge( $aChargeTxns, $aRefundedCharges, $aPayoutFailures ) );
	}

	/**
	 * You can filter the type of balance transactions
	 * https://stripe.com/docs/api/balance_transactions/list
	 * @param array $aParams
	 * @return Collection
	 */
	protected function getPayoutBalanceTransactions( $aParams = [] ) {
		$aRequest = array_merge(
			[
				'payout' => $this->getStripePayout()->id,
				'limit'  => 20
			],
			$aParams
		);
		return BalanceTransaction::all( $aRequest );
	}
}