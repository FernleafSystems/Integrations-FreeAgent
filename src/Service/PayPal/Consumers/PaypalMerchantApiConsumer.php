<?php

namespace FernleafSystems\Integrations\Freeagent\Service\PayPal\Consumers;

use FernleafSystems\Integrations\Freeagent\Service\PayPal\DataWrapper\PaypalMerchantApi;

trait PaypalMerchantApiConsumer {

	private ?PaypalMerchantApi $paypalMerchantApi = null;

	/**
	 * @return PaypalMerchantApi
	 */
	public function getPaypalMerchantApi() :PaypalMerchantApi {
		return $this->paypalMerchantApi;
	}

	/**
	 * @param PaypalMerchantApi $api
	 * @return $this
	 */
	public function setPaypalMerchantApi( PaypalMerchantApi $api ) {
		$this->paypalMerchantApi = $api;
		return $this;
	}
}