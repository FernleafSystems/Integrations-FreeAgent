<?php

namespace FernleafSystems\Integrations\Freeagent\Service\PayPal\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\DynProperties;
use PayPal\Service\PayPalAPIInterfaceServiceService;

/**
 * Class PaypalMerchantApi
 * @package FernleafSystems\Integrations\Freeagent\Service\PayPal\DataWrapper
 * @property array $api_config
 */
class PaypalMerchantApi {

	use DynProperties;

	/**
	 * @return PayPalAPIInterfaceServiceService
	 */
	public function api() {
		return new PayPalAPIInterfaceServiceService( $this->api_config );
	}

	/**
	 * @return array
	 * @deprecated
	 */
	public function getConfig() :array {
		return is_array( $this->api_config ) ? $this->api_config : [];
	}

	/**
	 * @param array $config
	 * @return $this
	 */
	public function setConfig( $config ) :self {
		$this->api_config = $config;
		return $this;
	}
}