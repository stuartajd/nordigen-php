<?php

namespace UnitTests\CoreTests;

use Nordigen\NordigenPHP\Exceptions\InstitutionExceptions\RateLimitError;
use Nordigen\NordigenPHP\Exceptions\ExceptionHandler;
use Nordigen\NordigenPHP\Exceptions\NordigenExceptions\NordigenException;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Psr7\Response;

class ExceptionHandlerTest extends TestCase
{
    /**
     * @covers \Nordigen\NordigenPHP\Exceptions\ExceptionHandler
     */
    public function testCorrectExceptionIsThrown()
    {
        $jsonBody = json_encode([
            'detail' => 'Rate limit exceeded',
            'type' => 'RateLimitError'
        ]);
        $response = new Response(429, [], $jsonBody);

        $this->expectException(RateLimitError::class);
        ExceptionHandler::handleException($response);
    }

    /**
     * @covers \Nordigen\NordigenPHP\Exceptions\ExceptionHandler
     */
    public function testNordigenExceptionIsThrownWhenNoMatch()
    {
        $jsonBody = json_encode([
            'detail' => 'Rate limit exceeded',
            'type' => 'SomeNewError'
        ]);
        $response = new Response(401, [], $jsonBody);

        $this->expectException(NordigenException::class);
        ExceptionHandler::handleException($response);
    }

    /**
     * @covers \Nordigen\NordigenPHP\Exceptions\ExceptionHandler
     */
    public function testNordigenExceptionIncludesFullErrorDetails()
    {
        $jsonBody = json_encode([
            'summary' => 'Authentication failed',
            'detail' => 'Invalid API credentials provided',
            'type' => 'SomeNewError'
        ]);
        $response = new Response(401, [], $jsonBody);

        try {
            ExceptionHandler::handleException($response);
            $this->fail('Expected NordigenException to be thrown');
        } catch (NordigenException $e) {
            $this->assertStringContainsString('Authentication failed', $e->getMessage());
            $this->assertStringContainsString('Invalid API credentials provided', $e->getMessage());
        }
    }
}
