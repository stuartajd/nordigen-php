<?php

namespace Nordigen\NordigenPHP\Exceptions;

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
     * @return void
     */
    private static function getExceptionType(array $response)
    {
        $errorType = $response['type'] ?? 'NordigenException';

        return $errorType;
    }

    /**
     * Handle Exception
     */
    public static function handleException(ResponseInterface $response): void
    {
        $content = $response->getBody()->getContents();
        $json = json_decode($content, true);
        $errorCode = $response->getStatusCode();

        // Handle cases where response might not be valid JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            $message = "HTTP {$errorCode} Error";
            if (! empty($content)) {
                $message .= ': '.trim($content);
            }
            throw new NordigenException($response, $message, $errorCode);
        }

        // Handle cases where JSON is a list (numeric array) instead of a dictionary
        if (is_array($json) && ! self::isAssociativeArray($json)) {
            // Try to extract error information from the list
            $messageParts = [];
            foreach ($json as $item) {
                if (is_string($item)) {
                    $messageParts[] = $item;
                } elseif (is_array($item) && isset($item['message'])) {
                    $messageParts[] = $item['message'];
                } elseif (is_array($item) && isset($item['detail'])) {
                    $messageParts[] = $item['detail'];
                } elseif (is_array($item) && isset($item['error'])) {
                    $messageParts[] = is_string($item['error']) ? $item['error'] : json_encode($item['error']);
                }
            }

            $message = ! empty($messageParts)
                ? implode(' ', $messageParts)
                : "HTTP {$errorCode} Error: ".trim($content);

            throw new NordigenException($response, trim($message), $errorCode);
        }

        // Handle cases where response is not an array at all
        if (! is_array($json)) {
            $message = "HTTP {$errorCode} Error";
            if (is_string($json)) {
                $message .= ': '.trim($json);
            } elseif (! empty($content)) {
                $message .= ': '.trim($content);
            }
            throw new NordigenException($response, $message, $errorCode);
        }

        $errorType = self::getExceptionType($json);
        $summary = $json['summary'] ?? '';
        $detail = $json['detail'] ?? '';

        // Build comprehensive error message
        $messageParts = [];
        if (! empty($summary)) {
            $messageParts[] = $summary;
        }
        if (! empty($detail)) {
            $messageParts[] = $detail;
        }

        // If no summary or detail, try to include other error information
        if (empty($messageParts)) {
            if (isset($json['message'])) {
                $messageParts[] = $json['message'];
            } elseif (isset($json['error'])) {
                $messageParts[] = is_string($json['error']) ? $json['error'] : json_encode($json['error']);
            } elseif (! empty($content)) {
                $messageParts[] = trim($content);
            }
        }

        $message = ! empty($messageParts) ? implode(' ', $messageParts) : "HTTP {$errorCode} Error";

        $exception = self::$institutionExceptionMap[$errorType] ?? NordigenException::class;
        throw new $exception($response, trim($message), $errorCode);
    }

    /**
     * Check if an array is associative (dictionary) or numeric (list)
     */
    private static function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return true; // Empty array is treated as associative
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
