# PayPal REST API Integration

This guide explains how to set up and use the PayPal REST API integration.

## Getting Your Credentials

1. Go to https://developer.paypal.com/dashboard/
2. Log in with your PayPal Business account (or create one)
3. Navigate to **Apps & Credentials**
4. Click **Create App** (or select an existing app)
5. Copy the **Client ID** and **Secret** (click "Show" to reveal the secret)

**Important:** PayPal provides separate credentials for Sandbox (testing) and Live (production) environments. Make sure you're using the correct credentials for your environment.

## Basic Setup

```php
use FernleafSystems\Integrations\Freeagent\Service\PayPal\DataWrapper\PaypalRestApi;
use FernleafSystems\Integrations\Freeagent\Service\PayPal\DataWrapper\PaypalRestApiConfigVO;

// Create configuration
$config = PaypalRestApiConfigVO::fromArray([
    'client_id'     => 'YOUR_CLIENT_ID',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    'environment'   => 'sandbox', // or 'production'
]);

// Create the API wrapper
$api = PaypalRestApi::fromConfigVO($config);

// Use the API
$client = $api->api();
```

## Configuration Methods

### From Array

```php
$api = PaypalRestApi::fromArray([
    'client_id'     => 'YOUR_CLIENT_ID',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    'environment'   => 'sandbox',
]);
```

### From Environment Variables

Set these environment variables:
- `PAYPAL_CLIENT_ID`
- `PAYPAL_CLIENT_SECRET`
- `PAYPAL_ENVIRONMENT` (optional, defaults to 'sandbox')

```php
$api = PaypalRestApi::fromEnv();

// Or with custom variable names:
$api = PaypalRestApi::fromEnv(
    'MY_PAYPAL_ID',
    'MY_PAYPAL_SECRET',
    'MY_PAYPAL_ENV'
);
```

### From a Provider Callback

```php
$api = PaypalRestApi::fromProvider(function() {
    // Load from your config system, database, etc.
    return [
        'client_id'     => $this->getConfigValue('paypal.client_id'),
        'client_secret' => $this->getConfigValue('paypal.client_secret'),
        'environment'   => $this->getConfigValue('paypal.environment'),
    ];
});
```

### Using Fluent Setters (Immutable)

```php
$config = (new PaypalRestApiConfigVO())
    ->withClientId('YOUR_CLIENT_ID')
    ->withClientSecret('YOUR_CLIENT_SECRET')
    ->withEnvironment('production');

$api = PaypalRestApi::fromConfigVO($config);
```

## Validating Configuration

Always validate your configuration before making API calls:

```php
$config = PaypalRestApiConfigVO::fromArray([...]);

if (!$config->isValid()) {
    $errors = $config->getValidationErrors();
    throw new Exception('Invalid config: ' . implode(', ', $errors));
}
```

## Verifying Credentials

Use the verification class to test that your credentials actually work:

```php
use FernleafSystems\Integrations\Freeagent\Service\PayPal\Verify\VerifyPaypalRestApiConfig;

$api = PaypalRestApi::fromConfigVO($config);
$verifier = (new VerifyPaypalRestApiConfig())->setPaypalRestApi($api);

// Quick format check (no API call)
if (!$verifier->verify()) {
    $result = $verifier->getVerificationResult();
    throw new Exception('Config invalid: ' . implode(', ', $result['errors']));
}

// Full verification with API call (recommended for initial setup)
if (!$verifier->verifyWithApiTest()) {
    $result = $verifier->getVerificationResult();
    throw new Exception('Credentials failed: ' . implode(', ', $result['errors']));
}

echo "Credentials verified successfully!";
```

## Token Caching (Recommended for Production)

PayPal OAuth tokens are valid for several hours. Caching them reduces API calls and improves performance:

```php
use PaypalServerSdkLib\Models\OAuthToken;

$api = PaypalRestApi::fromConfigVO($config);

// Provide cached tokens
$api->setTokenProvider(function (?OAuthToken $lastToken, $authManager) use ($cache) {
    $cached = $cache->get('paypal_oauth_token');
    if ($cached && !$authManager->isTokenExpired($cached)) {
        return $cached;
    }
    return $authManager->fetchToken();
});

// Save tokens when refreshed
$api->setTokenUpdateCallback(function (OAuthToken $token) use ($cache) {
    $cache->set('paypal_oauth_token', $token, $token->getExpiresIn());
});
```

### Simple Token Injection

If you already have a valid token:

```php
$token = new OAuthToken($accessToken, 'Bearer');
$token->setExpiresIn(32400);
$token->setExpiry(time() + 32400);

$api->setCachedToken($token);
```

## Environment Selection

| Environment | Use Case | API Endpoint |
|-------------|----------|--------------|
| `sandbox` | Development & testing | api-m.sandbox.paypal.com |
| `production` | Live transactions | api-m.paypal.com |

**Warning:** Sandbox credentials will NOT work in production and vice versa. Always ensure your credentials match your environment setting.

### Auto-Detect Environment

The config can attempt to auto-detect the environment based on the client ID format:

```php
$config = PaypalRestApiConfigVO::fromArray([
    'client_id'     => 'sb-xxxxx', // Sandbox IDs often start with 'sb-'
    'client_secret' => 'xxxxx',
])->withAutoDetectedEnvironment();

echo $config->environment; // 'sandbox'
```

## Complete Example

```php
use FernleafSystems\Integrations\Freeagent\Service\PayPal\DataWrapper\PaypalRestApi;
use FernleafSystems\Integrations\Freeagent\Service\PayPal\DataWrapper\PaypalRestApiConfigVO;
use FernleafSystems\Integrations\Freeagent\Service\PayPal\Verify\VerifyPaypalRestApiConfig;

// 1. Create and validate config
$config = PaypalRestApiConfigVO::fromArray([
    'client_id'     => 'AYourClientIdFromPayPalDashboard',
    'client_secret' => 'EYourSecretFromPayPalDashboard',
    'environment'   => 'sandbox',
]);

if (!$config->isValid()) {
    die('Invalid config: ' . implode(', ', $config->getValidationErrors()));
}

// 2. Create API wrapper
$api = PaypalRestApi::fromConfigVO($config);

// 3. Verify credentials (recommended for first-time setup)
$verifier = (new VerifyPaypalRestApiConfig())->setPaypalRestApi($api);
if (!$verifier->verifyWithApiTest()) {
    $result = $verifier->getVerificationResult();
    die('Verification failed: ' . implode(', ', $result['errors']));
}

// 4. Use the API
$client = $api->api();
$ordersController = $client->getOrdersController();
// ... make API calls
```

## Troubleshooting

### "Client ID is required" / "Client Secret is required"
Your configuration array is missing the required credentials. Check that you're passing `client_id` and `client_secret` keys.

### "Client ID appears too short to be valid"
PayPal client IDs are typically 80+ characters. Double-check you've copied the complete value.

### "OAuth authentication failed"
- Verify you're using the correct credentials for your environment (sandbox vs production)
- Check that your app is active in the PayPal Developer Dashboard
- Ensure the client secret hasn't been regenerated

### "Environment must be 'sandbox' or 'production'"
The environment value must be exactly `sandbox` or `production` (lowercase).
