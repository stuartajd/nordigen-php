<?php

namespace Nordigen\NordigenPHP\Exceptions;

use Nordigen\NordigenPHP\Exceptions\InstitutionExceptions;
use Nordigen\NordigenPHP\Exceptions\NordigenExceptions\NordigenException;
use Psr\Http\Message\ResponseInterface;

class ExceptionHandler
{
    private static array $institutionExceptionMap = [
        'UnknownRequestError' => InstitutionExceptions\UnknownRequestError::class,
        'AccessExpiredError' => InstitutionExceptions\AccessExpiredError::class,
        'AccountInactiveError' => InstitutionExceptions\AccountInactiveError::class,
        'ConnectionError' => InstitutionExceptions\InstitutionConnectionError::class,
        'ServiceError' => InstitutionExceptions\InstitutionServiceError::class,
        'RateLimitError' => InstitutionExceptions\RateLimitError::class,
    ];

    /**
     * Get exception type
     *
     * @param array $response
     * @return void
     */
    private static function getExceptionType(array $response)
    {
        $errorType = $response['type'] ?? 'NordigenException';
        return $errorType;
    }

    /**
     * Handle Exception
     *
     * @param ResponseInterface $response
     * @return void
     */
    public static function handleException(ResponseInterface $response): void
    {
        $content = $response->getBody()->getContents();
        $json = json_decode($content, true);
        $errorCode = $response->getStatusCode();
        
        // Handle cases where response might not be valid JSON
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
            $message = "HTTP {$errorCode} Error";
            if (!empty($content)) {
                $message .= ": " . trim($content);
            }
            throw new NordigenException($response, $message, $errorCode);
        }
        
        $errorType = self::getExceptionType($json);
        $summary = $json['summary'] ?? '';
        $detail = $json['detail'] ?? '';
        
        // Build comprehensive error message
        $messageParts = [];
        if (!empty($summary)) {
            $messageParts[] = $summary;
        }
        if (!empty($detail)) {
            $messageParts[] = $detail;
        }
        
        // If no summary or detail, try to include other error information
        if (empty($messageParts)) {
            if (isset($json['message'])) {
                $messageParts[] = $json['message'];
            } elseif (isset($json['error'])) {
                $messageParts[] = is_string($json['error']) ? $json['error'] : json_encode($json['error']);
            } elseif (!empty($content)) {
                $messageParts[] = trim($content);
            }
        }
        
        $message = !empty($messageParts) ? implode(' ', $messageParts) : "HTTP {$errorCode} Error";

        $exception = self::$institutionExceptionMap[$errorType] ?? NordigenException::class;
        throw new $exception($response, trim($message), $errorCode);
    }
}
