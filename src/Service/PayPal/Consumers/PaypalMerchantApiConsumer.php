<?php

namespace FernleafSystems\Integrations\Freeagent\Service\PayPal\Consumers;

use FernleafSystems\Integrations\Freeagent\Service\PayPal\DataWrapper\PaypalMerchantApi;

trait PaypalMerchantApiConsumer {

	/**
	 * @var PaypalMerchantApi
	 */
	private $oPaypalMerchantApi;

	/**
	 * @return PaypalMerchantApi
	 */
	public function getPaypalMerchantApi() {
		return $this->oPaypalMerchantApi;
	}

	/**
	 * @param PaypalMerchantApi $oConfig
	 * @return $this
	 */
	public function setPaypalMerchantApi( $oConfig ) {
		$this->oPaypalMerchantApi = $oConfig;
		return $this;
	}
}