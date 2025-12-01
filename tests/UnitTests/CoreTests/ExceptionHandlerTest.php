<?php

namespace UnitTests\CoreTests;

use GuzzleHttp\Psr7\Response;
use Nordigen\NordigenPHP\Exceptions\ExceptionHandler;
use Nordigen\NordigenPHP\Exceptions\InstitutionExceptions\RateLimitError;
use Nordigen\NordigenPHP\Exceptions\NordigenExceptions\NordigenException;
use PHPUnit\Framework\TestCase;

class ExceptionHandlerTest extends TestCase
{
    /**
     * @covers \Nordigen\NordigenPHP\Exceptions\ExceptionHandler
     */
    public function test_correct_exception_is_thrown()
    {
        $jsonBody = json_encode([
            'detail' => 'Rate limit exceeded',
            'type' => 'RateLimitError',
        ]);
        $response = new Response(429, [], $jsonBody);

        $this->expectException(RateLimitError::class);
        ExceptionHandler::handleException($response);
    }

    /**
     * @covers \Nordigen\NordigenPHP\Exceptions\ExceptionHandler
     */
    public function test_nordigen_exception_is_thrown_when_no_match()
    {
        $jsonBody = json_encode([
            'detail' => 'Rate limit exceeded',
            'type' => 'SomeNewError',
        ]);
        $response = new Response(401, [], $jsonBody);

        $this->expectException(NordigenException::class);
        ExceptionHandler::handleException($response);
    }

    /**
     * @covers \Nordigen\NordigenPHP\Exceptions\ExceptionHandler
     */
    public function test_nordigen_exception_includes_full_error_details()
    {
        $jsonBody = json_encode([
            'summary' => 'Authentication failed',
            'detail' => 'Invalid API credentials provided',
            'type' => 'SomeNewError',
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

    /**
     * @covers \Nordigen\NordigenPHP\Exceptions\ExceptionHandler
     */
    public function test_nordigen_exception_handles_list_response()
    {
        // Test case where API returns a list instead of a dictionary
        $jsonBody = json_encode(['Invalid data. Expected a dictionary, but got list.']);
        $response = new Response(400, [], $jsonBody);

        try {
            ExceptionHandler::handleException($response);
            $this->fail('Expected NordigenException to be thrown');
        } catch (NordigenException $e) {
            $this->assertStringContainsString('Invalid data. Expected a dictionary, but got list.', $e->getMessage());
        }
    }

    /**
     * @covers \Nordigen\NordigenPHP\Exceptions\ExceptionHandler
     */
    public function test_nordigen_exception_handles_list_with_error_objects()
    {
        // Test case where API returns a list with error objects
        $jsonBody = json_encode([
            ['message' => 'Invalid data. Expected a dictionary, but got list.'],
            ['detail' => 'Additional error information'],
        ]);
        $response = new Response(400, [], $jsonBody);

        try {
            ExceptionHandler::handleException($response);
            $this->fail('Expected NordigenException to be thrown');
        } catch (NordigenException $e) {
            $this->assertStringContainsString('Invalid data. Expected a dictionary, but got list.', $e->getMessage());
            $this->assertStringContainsString('Additional error information', $e->getMessage());
        }
    }
}
