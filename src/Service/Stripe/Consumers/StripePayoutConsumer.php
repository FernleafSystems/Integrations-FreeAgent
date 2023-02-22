<?php

namespace FernleafSystems\Integrations\Freeagent\Service\Stripe\Consumers;

use Stripe\Payout;

trait StripePayoutConsumer {

	private ?Payout $stripePayout = null;

	public function getStripePayout() :Payout {
		return $this->stripePayout;
	}

	public function setStripePayout( Payout $payout ) :self {
		$this->stripePayout = $payout;
		return $this;
	}
}