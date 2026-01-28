<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Service\PayPal\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\DynPropertiesClass;

/**
 * PayPal REST API Configuration Value Object
 *
 * ## Initial Setup - Getting Your Credentials
 *
 * 1. Go to https://developer.paypal.com/dashboard/
 * 2. Log in with your PayPal Business account (or create one)
 * 3. Navigate to "Apps & Credentials"
 * 4. Click "Create App" (or select existing app)
 * 5. Copy the "Client ID" and "Secret" (click "Show" to reveal)
 * 6. Note: Sandbox credentials are for testing, Live for production
 *
 * ## Environment Selection
 * - Use 'sandbox' for development/testing (default)
 * - Use 'production' for live transactions
 * - Sandbox credentials will NOT work in production and vice versa
 *
 * @property string $client_id
 * @property string $client_secret
 * @property string $environment
 */
class PaypalRestApiConfigVO extends DynPropertiesClass {

	public const ENV_SANDBOX = 'sandbox';
	public const ENV_PRODUCTION = 'production';

	/**
	 * Create config from an associative array.
	 *
	 * @param array $config Keys: 'client_id', 'client_secret', 'environment' (optional)
	 */
	public static function fromArray( array $config ): self {
		$vo = new self();
		$vo->applyFromArray( $config );
		return $vo;
	}

	/**
	 * Create config from environment variables.
	 *
	 * @param string $clientIdEnv     Environment variable name for client ID
	 * @param string $clientSecretEnv Environment variable name for client secret
	 * @param string $environmentEnv  Environment variable name for environment (sandbox/production)
	 */
	public static function fromEnv(
		string $clientIdEnv = 'PAYPAL_CLIENT_ID',
		string $clientSecretEnv = 'PAYPAL_CLIENT_SECRET',
		string $environmentEnv = 'PAYPAL_ENVIRONMENT'
	): self {
		return self::fromArray( [
			'client_id'     => \getenv( $clientIdEnv ) ?: '',
			'client_secret' => \getenv( $clientSecretEnv ) ?: '',
			'environment'   => \getenv( $environmentEnv ) ?: self::ENV_SANDBOX,
		] );
	}

	/**
	 * Create config from a callable provider.
	 * The provider should return an array with keys: 'client_id', 'client_secret', 'environment'
	 *
	 * @param callable $provider Returns array with config values
	 */
	public static function fromProvider( callable $provider ): self {
		return self::fromArray( $provider() );
	}

	/**
	 * Set the client ID.
	 */
	public function withClientId( string $clientId ): self {
		$clone = clone $this;
		$clone->client_id = $clientId;
		return $clone;
	}

	/**
	 * Set the client secret.
	 */
	public function withClientSecret( string $clientSecret ): self {
		$clone = clone $this;
		$clone->client_secret = $clientSecret;
		return $clone;
	}

	/**
	 * Set the environment (sandbox or production).
	 */
	public function withEnvironment( string $environment ): self {
		$clone = clone $this;
		$clone->environment = $environment;
		return $clone;
	}

	/**
	 * Auto-detect environment based on client ID prefix.
	 * PayPal sandbox client IDs typically start with 'sb-' or contain 'sandbox'.
	 */
	public function withAutoDetectedEnvironment(): self {
		$clone = clone $this;
		$clientId = $clone->client_id ?? '';

		if ( \str_starts_with( $clientId, 'sb-' ) || \stripos( $clientId, 'sandbox' ) !== false ) {
			$clone->environment = self::ENV_SANDBOX;
		}
		else {
			$clone->environment = self::ENV_PRODUCTION;
		}

		return $clone;
	}

	/**
	 * Check if the configuration has all required values and they are valid.
	 */
	public function isValid(): bool {
		return empty( $this->getValidationErrors() );
	}

	/**
	 * Get validation errors for the configuration.
	 *
	 * @return string[] Array of error messages
	 */
	public function getValidationErrors(): array {
		$errors = [];

		$clientId = $this->client_id ?? '';
		$clientSecret = $this->client_secret ?? '';
		$environment = $this->environment ?? '';

		if ( empty( $clientId ) ) {
			$errors[] = 'Client ID is required';
		}
		elseif ( !\is_string( $clientId ) ) {
			$errors[] = 'Client ID must be a string';
		}
		elseif ( \strlen( $clientId ) < 10 ) {
			$errors[] = 'Client ID appears too short to be valid';
		}

		if ( empty( $clientSecret ) ) {
			$errors[] = 'Client Secret is required';
		}
		elseif ( !\is_string( $clientSecret ) ) {
			$errors[] = 'Client Secret must be a string';
		}
		elseif ( \strlen( $clientSecret ) < 10 ) {
			$errors[] = 'Client Secret appears too short to be valid';
		}

		if ( !empty( $environment ) && !\in_array( $environment, [ self::ENV_SANDBOX, self::ENV_PRODUCTION ], true ) ) {
			$errors[] = \sprintf( "Environment must be '%s' or '%s'", self::ENV_SANDBOX, self::ENV_PRODUCTION );
		}

		return $errors;
	}

	/**
	 * Convert to array format suitable for PaypalRestApi::setConfig()
	 *
	 * @return array{client_id: string, client_secret: string, environment: string}
	 */
	public function toConfigArray(): array {
		return [
			'client_id'     => $this->client_id ?? '',
			'client_secret' => $this->client_secret ?? '',
			'environment'   => $this->environment ?? self::ENV_SANDBOX,
		];
	}
}
