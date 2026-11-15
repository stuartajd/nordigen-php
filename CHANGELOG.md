# Changelog

## [1.1.8] - 2026-11-15

- Requests time out after 30 seconds (10 seconds to connect)
- The response body can be read again from a thrown exception
- `getRateLimit()` and `getRateLimitRemaining()` return 0 before transactions are fetched
- Requires PHP 8.0
- Added tests for the reconfirmation endpoints

## [1.1.7] - 2026-03-25

- Added `EulaExpiredError`, thrown when the end user agreement has expired

## [1.1.6] - 2025-12-01

- Fixed reconfirming an end user agreement to use POST
- Clearer exception messages, including for responses that aren't JSON

## [1.1.5] - 2025-09-12

- Added the `reconfirmation` option when creating an end user agreement and in `initSession`
- Added retrieving an end user agreement's reconfirmation
- Added `getRateLimit()` and `getRateLimitRemaining()` to accounts

## [1.1.4] - 2025-09-12

- Fixed dynamic property creation - https://github.com/nordigen/nordigen-php/pull/27
- Added reconfirm end of user agreement endpoint

## [1.1.3] - 2025-04-07

- Add maintenance notice to README

## [1.1.2] - 2024-02-13

- Changed BASE_URL from ob to bankaccountdata

## [1.1.1] - 2023-06-22

- Changed outdated Nordigen URLs to GoCardless

## [1.0.2] - 2022-05-03

- Move access token to request handler
- Fix trx GET parameter for date filter

## [1.0.1] - 2022-04-05

- Composer. Add curl extensions and minimum-stability version
- Update documentation
- Add Github PR/Issues templates


## [1.0.0] - 2022-04-05

- Initial release

