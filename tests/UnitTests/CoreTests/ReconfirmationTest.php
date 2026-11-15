<?php

namespace UnitTests\CoreTests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Nordigen\NordigenPHP\API\NordigenClient;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Nordigen\NordigenPHP\API\EndUserAgreement
 * @covers \Nordigen\NordigenPHP\API\Account
 */
class ReconfirmationTest extends TestCase
{
    private array $requests = [];

    private function client(Response ...$responses): NordigenClient
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->requests));

        return new NordigenClient('id', 'key', new Client(['handler' => $stack, 'base_uri' => NordigenClient::BASE_URL]));
    }

    public function test_an_agreement_is_created_with_the_reconfirmation_flag()
    {
        $this->client(new Response(201, [], '{"id":"agreement-1"}'))
            ->endUserAgreement->createEndUserAgreement('BANK', ['details'], 90, 730, true);

        $request = $this->requests[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('agreements/enduser/', $request->getUri()->getPath());
        $this->assertTrue(json_decode((string) $request->getBody(), true)['reconfirmation']);
    }

    public function test_a_reconfirmation_is_created_with_an_optional_redirect()
    {
        $client = $this->client(
            new Response(201, [], '{"reconfirmation_url":"https://example.com/reconfirm"}'),
            new Response(201, [], '{"reconfirmation_url":"https://example.com/reconfirm"}')
        );

        $result = $client->endUserAgreement->reconfirmEndUserAgreement('agreement-1');
        $client->endUserAgreement->reconfirmEndUserAgreement('agreement-1', 'https://app.example/back');

        $this->assertSame('https://example.com/reconfirm', $result['reconfirmation_url']);
        $this->assertSame('POST', $this->requests[0]['request']->getMethod());
        $this->assertStringEndsWith('agreements/enduser/agreement-1/reconfirm/', $this->requests[0]['request']->getUri()->getPath());
        $this->assertSame('{}', (string) $this->requests[0]['request']->getBody());
        $this->assertSame('{"redirect":"https:\/\/app.example\/back"}', (string) $this->requests[1]['request']->getBody());
    }

    public function test_a_reconfirmation_is_retrieved()
    {
        $result = $this->client(new Response(200, [], '{"accounts":{"account-1":{"reconfirmed":"","rejected":""}}}'))
            ->endUserAgreement->retrieveEndUserAgreementReconfirmation('agreement-1');

        $this->assertSame('GET', $this->requests[0]['request']->getMethod());
        $this->assertStringEndsWith('agreements/enduser/agreement-1/reconfirm/', $this->requests[0]['request']->getUri()->getPath());
        $this->assertSame('', $result['accounts']['account-1']['reconfirmed']);
    }

    public function test_the_rate_limit_is_zero_before_any_transactions_are_fetched()
    {
        $account = $this->client()->account('account-1');

        $this->assertSame(0, $account->getRateLimit());
        $this->assertSame(0, $account->getRateLimitRemaining());
    }
}
