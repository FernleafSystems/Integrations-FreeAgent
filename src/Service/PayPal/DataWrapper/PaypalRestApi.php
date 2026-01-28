<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Service\PayPal\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\DynProperties;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\PaypalServerSdkClient;

/**
 * @property array $api_config - ['client_id', 'client_secret', 'environment']
 */
class PaypalRestApi {

	use DynProperties;

	private ?PaypalServerSdkClient $client = null;

	public function api(): PaypalServerSdkClient {
		if ( $this->client === null ) {
			$cfg = $this->api_config;
			$env = ( $cfg[ 'environment' ] ?? 'sandbox' ) === 'production'
				? Environment::PRODUCTION
				: Environment::SANDBOX;

			$this->client = PaypalServerSdkClientBuilder::init()
				->clientCredentialsAuthCredentials(
					ClientCredentialsAuthCredentialsBuilder::init(
						$cfg[ 'client_id' ],
						$cfg[ 'client_secret' ]
					)
				)
				->environment( $env )
				->build();
		}
		return $this->client;
	}

	public function setConfig( array $config ): self {
		$this->api_config = $config;
		$this->client = null;
		return $this;
	}
}
