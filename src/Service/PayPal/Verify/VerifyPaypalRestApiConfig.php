<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Service\PayPal\Verify;

use FernleafSystems\Integrations\Freeagent\Service\PayPal\Consumers\PaypalRestApiConsumer;

/**
 * Verify PayPal REST API configuration and credentials.
 *
 * This class provides two levels of verification:
 * 1. Format validation (no API call) - checks that config values are present and properly formatted
 * 2. API test (makes real API call) - verifies credentials by attempting to fetch an OAuth token
 *
 * Usage:
 * ```php
 * $verifier = (new VerifyPaypalRestApiConfig())->setPaypalRestApi($api);
 *
 * // Quick format check (no network call)
 * if (!$verifier->verify()) {
 *     $result = $verifier->getVerificationResult();
 *     echo "Config errors: " . implode(', ', $result['errors']);
 * }
 *
 * // Full verification with API test
 * if (!$verifier->verifyWithApiTest()) {
 *     $result = $verifier->getVerificationResult();
 *     echo "Verification failed: " . implode(', ', $result['errors']);
 * }
 * ```
 */
class VerifyPaypalRestApiConfig {

	use PaypalRestApiConsumer;

	private array $verificationResult = [];

	/**
	 * Verify that the config format is valid (no API call).
	 *
	 * Checks:
	 * - Client ID is present and appears valid
	 * - Client Secret is present and appears valid
	 * - Environment is valid (if specified)
	 *
	 * @return bool True if config format is valid
	 */
	public function verify(): bool {
		try {
			return $this->runVerify();
		}
		catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Verify that the config format is valid (no API call).
	 *
	 * @return bool True if config format is valid
	 * @throws \Exception If verification fails
	 */
	public function runVerify(): bool {
		$this->verificationResult = [
			'valid'         => false,
			'errors'        => [],
			'token_fetched' => false,
		];

		$api = $this->getPaypalRestApi();
		if ( $api === null ) {
			$this->verificationResult[ 'errors' ][] = 'PaypalRestApi instance not set';
			throw new \Exception( 'PaypalRestApi instance not set' );
		}

		$configVO = $api->getConfigVO();
		$errors = $configVO->getValidationErrors();

		if ( !empty( $errors ) ) {
			$this->verificationResult[ 'errors' ] = $errors;
			throw new \Exception( 'Config validation failed: '.\implode( ', ', $errors ) );
		}

		$this->verificationResult[ 'valid' ] = true;
		return true;
	}

	/**
	 * Verify credentials work by fetching an OAuth token.
	 * This makes an actual API call to PayPal.
	 *
	 * @return bool True if credentials are valid and token was fetched
	 */
	public function verifyWithApiTest(): bool {
		try {
			return $this->runVerifyWithApiTest();
		}
		catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Verify credentials work by fetching an OAuth token.
	 * This makes an actual API call to PayPal.
	 *
	 * @return bool True if credentials are valid and token was fetched
	 * @throws \Exception If verification fails
	 */
	public function runVerifyWithApiTest(): bool {
		// First verify the config format
		$this->runVerify();

		$api = $this->getPaypalRestApi();

		try {
			// Attempt to make an API call - this will trigger OAuth token fetch
			// We use a lightweight call that doesn't require any specific permissions
			$client = $api->api();

			// The SDK will attempt to get an OAuth token when we try to use the client
			// We can verify by checking if we can get the auth manager
			$authManager = $client->getClientCredentialsAuth();

			// Try to fetch a token - this verifies the credentials work
			$token = $authManager->fetchToken();

			if ( $token === null || empty( $token->getAccessToken() ) ) {
				$this->verificationResult[ 'errors' ][] = 'Failed to obtain OAuth token from PayPal';
				$this->verificationResult[ 'valid' ] = false;
				throw new \Exception( 'Failed to obtain OAuth token from PayPal' );
			}

			$this->verificationResult[ 'token_fetched' ] = true;
			$this->verificationResult[ 'valid' ] = true;
			return true;
		}
		catch ( \PaypalServerSdkLib\Exceptions\OAuthProviderException $e ) {
			$this->verificationResult[ 'errors' ][] = 'OAuth authentication failed: '.$e->getMessage();
			$this->verificationResult[ 'valid' ] = false;
			throw new \Exception( 'OAuth authentication failed: '.$e->getMessage(), 0, $e );
		}
		catch ( \PaypalServerSdkLib\Exceptions\ApiException $e ) {
			$this->verificationResult[ 'errors' ][] = 'API error during verification: '.$e->getMessage();
			$this->verificationResult[ 'valid' ] = false;
			throw new \Exception( 'API error during verification: '.$e->getMessage(), 0, $e );
		}
		catch ( \Exception $e ) {
			// Don't re-wrap our own exceptions
			if ( \str_starts_with( $e->getMessage(), 'Config validation failed:' )
				 || \str_starts_with( $e->getMessage(), 'PaypalRestApi instance not set' ) ) {
				throw $e;
			}

			$this->verificationResult[ 'errors' ][] = 'Unexpected error during verification: '.$e->getMessage();
			$this->verificationResult[ 'valid' ] = false;
			throw new \Exception( 'Unexpected error during verification: '.$e->getMessage(), 0, $e );
		}
	}

	/**
	 * Get detailed verification result.
	 *
	 * @return array{valid: bool, errors: string[], token_fetched: bool}
	 */
	public function getVerificationResult(): array {
		if ( empty( $this->verificationResult ) ) {
			return [
				'valid'         => false,
				'errors'        => [ 'Verification not yet performed' ],
				'token_fetched' => false,
			];
		}
		return $this->verificationResult;
	}

	/**
	 * Reset the verification state.
	 */
	public function reset(): self {
		$this->verificationResult = [];
		return $this;
	}
}
