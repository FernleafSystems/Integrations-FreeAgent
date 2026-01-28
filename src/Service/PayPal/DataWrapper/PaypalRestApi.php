<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Service\PayPal\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\DynProperties;
use PaypalServerSdkLib\PaypalServerSdkClientBuilder;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\Models\OAuthToken;
use PaypalServerSdkLib\PaypalServerSdkClient;

/**
 * @property array $api_config - ['client_id', 'client_secret', 'environment']
 */
class PaypalRestApi {

	use DynProperties;

	private ?PaypalServerSdkClient $client = null;

	private ?PaypalRestApiConfigVO $configVO = null;

	/**
	 * Token provider callback.
	 * Signature: callable(OAuthToken|null $lastToken, ClientCredentialsAuthManager $authManager): OAuthToken
	 * @var callable|null
	 */
	private $tokenProvider = null;

	/**
	 * Token update callback.
	 * Signature: callable(OAuthToken $token): void
	 * @var callable|null
	 */
	private $tokenUpdateCallback = null;

	private ?OAuthToken $cachedToken = null;

	/**
	 * Create from an associative array.
	 *
	 * @param array $config Keys: 'client_id', 'client_secret', 'environment' (optional)
	 */
	public static function fromArray( array $config ): self {
		return ( new self() )->setConfig( $config );
	}

	/**
	 * Create from a PaypalRestApiConfigVO instance.
	 */
	public static function fromConfigVO( PaypalRestApiConfigVO $configVO ): self {
		return ( new self() )->setConfigVO( $configVO );
	}

	/**
	 * Create from environment variables.
	 *
	 * @param string $clientIdEnv     Environment variable name for client ID
	 * @param string $clientSecretEnv Environment variable name for client secret
	 * @param string $environmentEnv  Environment variable name for environment
	 */
	public static function fromEnv(
		string $clientIdEnv = 'PAYPAL_CLIENT_ID',
		string $clientSecretEnv = 'PAYPAL_CLIENT_SECRET',
		string $environmentEnv = 'PAYPAL_ENVIRONMENT'
	): self {
		return self::fromConfigVO( PaypalRestApiConfigVO::fromEnv( $clientIdEnv, $clientSecretEnv, $environmentEnv ) );
	}

	/**
	 * Create from a callable provider.
	 * The provider should return an array with keys: 'client_id', 'client_secret', 'environment'
	 *
	 * @param callable $provider Returns array with config values
	 */
	public static function fromProvider( callable $provider ): self {
		return self::fromConfigVO( PaypalRestApiConfigVO::fromProvider( $provider ) );
	}

	public function api(): PaypalServerSdkClient {
		if ( $this->client === null ) {
			$cfg = $this->api_config;
			$env = ( $cfg[ 'environment' ] ?? 'sandbox' ) === 'production'
				? Environment::PRODUCTION
				: Environment::SANDBOX;

			$credentialsBuilder = ClientCredentialsAuthCredentialsBuilder::init(
				$cfg[ 'client_id' ],
				$cfg[ 'client_secret' ]
			);

			if ( $this->cachedToken !== null ) {
				$credentialsBuilder->oAuthToken( $this->cachedToken );
			}

			if ( $this->tokenProvider !== null ) {
				$credentialsBuilder->oAuthTokenProvider( $this->tokenProvider );
			}

			if ( $this->tokenUpdateCallback !== null ) {
				$credentialsBuilder->oAuthOnTokenUpdate( $this->tokenUpdateCallback );
			}

			$this->client = PaypalServerSdkClientBuilder::init()
				->clientCredentialsAuthCredentials( $credentialsBuilder )
				->environment( $env )
				->build();
		}
		return $this->client;
	}

	/**
	 * Set the full configuration array.
	 *
	 * @param array $config Keys: 'client_id', 'client_secret', 'environment'
	 */
	public function setConfig( array $config ): self {
		$this->api_config = $config;
		$this->configVO = null;
		$this->client = null;
		return $this;
	}

	/**
	 * Set configuration from a PaypalRestApiConfigVO instance.
	 */
	public function setConfigVO( PaypalRestApiConfigVO $configVO ): self {
		$this->configVO = $configVO;
		$this->api_config = $configVO->toConfigArray();
		$this->client = null;
		return $this;
	}

	/**
	 * Get the configuration value object.
	 * Creates one from the current config if not already set.
	 */
	public function getConfigVO(): PaypalRestApiConfigVO {
		if ( $this->configVO === null ) {
			$this->configVO = PaypalRestApiConfigVO::fromArray( $this->api_config ?? [] );
		}
		return $this->configVO;
	}

	/**
	 * Check if the current configuration is valid.
	 */
	public function hasValidConfig(): bool {
		return $this->getConfigVO()->isValid();
	}

	/**
	 * Set the client ID.
	 */
	public function setClientId( string $clientId ): self {
		$config = $this->api_config ?? [];
		$config[ 'client_id' ] = $clientId;
		return $this->setConfig( $config );
	}

	/**
	 * Set the client secret.
	 */
	public function setClientSecret( string $clientSecret ): self {
		$config = $this->api_config ?? [];
		$config[ 'client_secret' ] = $clientSecret;
		return $this->setConfig( $config );
	}

	/**
	 * Set the environment (sandbox or production).
	 */
	public function setEnvironment( string $environment ): self {
		$config = $this->api_config ?? [];
		$config[ 'environment' ] = $environment;
		return $this->setConfig( $config );
	}

	/**
	 * Set a token provider callback for lazy token loading.
	 *
	 * The callback receives the last known token (or null) and the auth manager,
	 * and should return an OAuthToken instance.
	 *
	 * Example:
	 * ```php
	 * $api->setTokenProvider(function (?OAuthToken $lastToken, $authManager) use ($cache) {
	 *     $cached = $cache->get('paypal_oauth_token');
	 *     if ($cached && !$authManager->isTokenExpired($cached)) {
	 *         return $cached;
	 *     }
	 *     return $authManager->fetchToken();
	 * });
	 * ```
	 *
	 * @param callable $provider Signature: (OAuthToken|null, ClientCredentialsAuthManager): OAuthToken
	 */
	public function setTokenProvider( callable $provider ): self {
		$this->tokenProvider = $provider;
		$this->client = null;
		return $this;
	}

	/**
	 * Set a callback to be notified when the token is updated/refreshed.
	 *
	 * Use this to persist tokens to your cache/storage.
	 *
	 * Example:
	 * ```php
	 * $api->setTokenUpdateCallback(function (OAuthToken $token) use ($cache) {
	 *     $cache->set('paypal_oauth_token', $token, $token->getExpiresIn());
	 * });
	 * ```
	 *
	 * @param callable $callback Signature: (OAuthToken): void
	 */
	public function setTokenUpdateCallback( callable $callback ): self {
		$this->tokenUpdateCallback = $callback;
		$this->client = null;
		return $this;
	}

	/**
	 * Set a cached OAuth token to use for API requests.
	 *
	 * This is useful when you have a previously-obtained token that's still valid.
	 */
	public function setCachedToken( OAuthToken $token ): self {
		$this->cachedToken = $token;
		$this->client = null;
		return $this;
	}

	/**
	 * Get the cached OAuth token, if set.
	 */
	public function getCachedToken(): ?OAuthToken {
		return $this->cachedToken;
	}

	/**
	 * Clear the cached token.
	 */
	public function clearCachedToken(): self {
		$this->cachedToken = null;
		$this->client = null;
		return $this;
	}
}
