<?php

namespace FernleafSystems\Integrations\Freeagent\Service\Stripe\Consumers;

use Stripe\Payout;

trait StripePayoutConsumer {

	/**
	 * @var Payout
	 */
	private $oStripePayout;

	/**
	 * @return Payout
	 */
	public function getStripePayout() {
		return $this->oStripePayout;
	}

	/**
	 * @param Payout $oPayout
	 * @return $this
	 */
	public function setStripePayout( $oPayout ) {
		$this->oStripePayout = $oPayout;
		return $this;
	}
}