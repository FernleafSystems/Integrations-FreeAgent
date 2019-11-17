<?php

namespace FernleafSystems\Integrations\Freeagent\Service\PayPal\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;
use PayPal\Service\PayPalAPIInterfaceServiceService;

/**
 * Class PaypalMerchantApi
 * @package FernleafSystems\Integrations\Freeagent\Service\PayPal\DataWrapper
 * @property array $api_config
 */
class PaypalMerchantApi {

	use StdClassAdapter;

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
	public function getConfig() {
		return is_array( $this->api_config ) ? $this->api_config : [];
	}

	/**
	 * @param array $aApiConfig
	 * @return $this
	 */
	public function setConfig( $aApiConfig ) {
		return $this->setParam( 'api_config', $aApiConfig );
	}
}