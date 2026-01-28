<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Service\PayPal\Consumers;

use FernleafSystems\Integrations\Freeagent\Service\PayPal\DataWrapper\PaypalRestApi;

trait PaypalRestApiConsumer {

	private ?PaypalRestApi $paypalRestApi = null;

	public function getPaypalRestApi(): ?PaypalRestApi {
		return $this->paypalRestApi;
	}

	public function setPaypalRestApi( PaypalRestApi $api ): self {
		$this->paypalRestApi = $api;
		return $this;
	}
}
