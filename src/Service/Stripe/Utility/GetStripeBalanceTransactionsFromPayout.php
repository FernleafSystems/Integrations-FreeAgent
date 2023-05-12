<?php

namespace FernleafSystems\Integrations\Freeagent\Service\Stripe\Utility;

use FernleafSystems\Integrations\Freeagent\Service\Stripe;
use Stripe\{
	BalanceTransaction,
	Collection
};

class GetStripeBalanceTransactionsFromPayout {

	use Stripe\Consumers\StripePayoutConsumer;

	/**
	 * @return BalanceTransaction[]
	 * @throws \Exception
	 */
	public function retrieve() :array {
		$PO = $this->getStripePayout();

		/** @var BalanceTransaction[] $transactions */
		$transactions = [];

		$sanityTotal = 0;
		/** @var BalanceTransaction $balTxn */
		foreach ( $this->getPayoutBalanceTransactions()->autoPagingIterator() as $balTxn ) {

			switch ( $balTxn->type ) {
				case 'adjustment':
				case 'charge':
				case 'refund':
				case 'payout_failure':
				case 'transfer_failure':
				case 'stripe_fee':
				case 'payment':
					/** e.g. payment = Link payment */
					$transactions[] = $balTxn;
					$sanityTotal += $balTxn->net;
					break;
				case 'payout':
				default:
					break;
			}
		}

		if ( $sanityTotal != $PO->amount ) {
			throw new \Exception( sprintf( 'Total tally %s does not match transfer amount %s',
				$sanityTotal, $PO->amount ) );
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

		return $transactions;
	}

	/**
	 * You can filter the type of balance transactions
	 * https://stripe.com/docs/api/balance_transactions/list
	 * @param array $params
	 * @return Collection
	 */
	protected function getPayoutBalanceTransactions( $params = [] ) {
		return BalanceTransaction::all( array_merge(
			[
				'payout' => $this->getStripePayout()->id,
				'limit'  => 20
			],
			$params
		) );
	}
}