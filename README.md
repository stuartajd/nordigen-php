# Nordigen PHP Library

This is a PHP client library for [GoCardless Bank Account Data](https://gocardless.com/bank-account-data/).

For a full list of endpoints and arguments, see the [docs](https://developer.gocardless.com/bank-account-data/quick-start-guide).

Before starting to use API you will need to create a new secret and get your `SECRET_ID` and `SECRET_KEY` from the [Nordigen's Open Banking Portal](https://bankaccountdata.gocardless.com/user-secrets/).


## Requirements

* PHP >= 8.0

## Installation

Install library via composer:

```sh
composer require stuartajd/nordigen-php
```

## Example application

Laravel example application can be found in `example` directory

## Quickstart

To use the library, use Composer's [autoload](https://getcomposer.org/doc/01-basic-usage.md#autoloading):

```php
require_once('vendor/autoload.php');
```

```php
// Get secretId and secretKey from bankaccoutndata.gocardless.com portal and pass them to NordigenClient
$secretId  = "YOUR_SECRET_ID";
$secretKey = "YOUR_SECRET_KEY";

$client = new \Nordigen\NordigenPHP\API\NordigenClient($secretId, $secretKey);

// Generate new access token. Token is valid for 24 hours
// Token is automatically injected into every response
$token = $client->createAccessToken();

// Get access token
$accessToken = $client->getAccessToken();
// Get refresh token
$refreshToken = $client->getRefreshToken();

// Exchange refresh token for new access token
$newToken = $client->refreshAccessToken($refreshToken);

// Get list of institutions by country. Country should be in ISO 3166 standard.
$institutions = $client->institution->getInstitutionsByCountry("LV");

// Institution id can be gathered from getInstitutions response.
// Example Revolut ID
$institutionId = "REVOLUT_REVOGB21";
$redirectUri = "https://nordigen.com";

// Initialize new bank connection session
$session = $client->initSession($institutionId, $redirectUri);

// Get link to authorize in the bank
// Authorize with your bank via this link, to gain access to account data
$link = $session["link"];
// requisition id is needed to get accountId in the next step
$requisitionId = $session["requisition_id"];
```

After successful authorization with a bank you can fetch your data (details, balances, transactions)

## Fetching account metadata, balances, details and transactions

```php
// Get account id after completed authorization with a bank
$requisitionData = $client->requisition->getRequisition($requisitionId);
// Get account id from the array of accounts
$accountId = $requisitionData["accounts"][0];

// Instantiate account object
$account = $client->account($accountId);

// Fetch account metadata
$metadata = $account->getAccountMetaData();
// Fetch account balances
$balances = $account->getAccountBalances();
// Fetch account details
$details = $account->getAccountDetails();
// Fetch account transactions
$transactions = $account->getAccountTransactions();

// Optional. You can filter transactions by specific date range
$transactions = $account->getAccountTransactions("2021-12-01", "2022-01-30");

// Get premium transactions
// Optional parameters country, dateFrom, dateTo
$premiumTransactions = $account->getPremiumAccountTransactions();
```

In order to use Premium endpoints please contact our [bank-account-data-support@gocardless.com](bank-account-data-support@gocardless.com)

## Reconfirming access (UK)

UK banks ask users to reconfirm access every 90 days. With reconfirmation, they can do that on a GoCardless page without logging in to their bank again. See the [GoCardless guide](https://bankaccountdata.zendesk.com/hc/en-gb/articles/20808205335452-90-day-reconfirmation-of-consent-in-the-UK-without-SCA) for the rules, such as which banks support it and when a user can reconfirm.

```php
// Create the connection with reconfirmation turned on (the last argument).
// The agreement has to be valid for more than 90 days; use the institution's
// max_access_valid_for_days_reconfirmation value.
$session = $client->initSession($institutionId, $redirectUri, 90, 730, null, ['details', 'balances', 'transactions'], null, null, null, null, true);
$agreementId = $session["agreement_id"];

// Create a reconfirmation link to send the user to. It is valid for 24 hours.
// The optional second argument overrides the redirect the user returns to.
$reconfirmation = $client->endUserAgreement->reconfirmEndUserAgreement($agreementId, "https://example.com/reconfirmed");
$link = $reconfirmation["reconfirmation_url"];

// Check whether the user has reconfirmed. Each account has its own
// "reconfirmed" and "rejected" time, empty until the user acts on it.
$reconfirmation = $client->endUserAgreement->retrieveEndUserAgreementReconfirmation($agreementId);
$accounts = $reconfirmation["accounts"];
```

## Rate limits

Banks limit how often each account can be read. After fetching transactions you can see where you stand:

```php
$transactions = $account->getAccountTransactions();

$account->getRateLimit();          // requests allowed in the current window
$account->getRateLimitRemaining(); // requests left in the current window
```

Both return 0 until transactions have been fetched.

## Errors

A failed request throws an exception that extends `Nordigen\NordigenPHP\Exceptions\BaseException`. The ones worth handling yourself are in `Nordigen\NordigenPHP\Exceptions\InstitutionExceptions`:

* `AccessExpiredError` and `EulaExpiredError` - access has ended, so the user has to connect the bank again
* `AccountInactiveError` - the account was closed or no longer exists
* `RateLimitError` - the account's rate limit was reached; try again later

Anything else is thrown as `NordigenExceptions\NordigenException`.

```php
try {
    $transactions = $account->getAccountTransactions();
} catch (\Nordigen\NordigenPHP\Exceptions\BaseException $exception) {
    $status = $exception->getResponse()->getStatusCode();
    $body = json_decode($exception->getResponse()->getBody()->getContents(), true);
}
```

Requests time out after 30 seconds (10 seconds to connect). A timeout throws Guzzle's own exception, not a `BaseException`.

## Tests

```php
./vendor/bin/phpunit
```
