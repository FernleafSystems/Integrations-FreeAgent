<?php

namespace FernleafSystems\Integrations\Freeagent\Service\Stripe\Utility;

use FernleafSystems\Integrations\Freeagent\Service\Stripe;
use Stripe\BalanceTransaction;

class SumTotalFeesForStripePayout {

	use Stripe\Consumers\StripePayoutConsumer;

	public function count() {

		$oFeeCollection = BalanceTransaction::all(
			[
				'payout' => $this->getStripePayout()->id,
				'type'   => 'charge',
				'limit'  => 20
			]
		);

		$nTotalFees = 0;
		foreach ( $oFeeCollection->autoPagingIterator() as $oStripeFee ) {
			$nTotalFees += $oStripeFee->fee;
		}

		return $nTotalFees;
	}
}