<?php
namespace Sipay\HttpClients;

use InvalidArgumentException;
use Exception;

class HttpClientsFactory
{
    private function __construct()
    {
        // a factory constructor should never be invoked
    }

    /**
     * HTTP client generation.
     *
     * @param SipayHttpClientInterface|Client|string|null $handler
     *
     * @throws Exception                If the cURL extension isn't available (if required).
     * @throws InvalidArgumentException If the http client handler isn't "curl" or an instance of Sipay\HttpClients\SipayHttpClientInterface.
     *
     * @return SipayHttpClientInterface
     */
    public static function createHttpClient($handler)
    {
        if (!$handler) {
            return self::detectDefaultClient();
        }

        if ($handler instanceof SipayHttpClientInterface) {
            return $handler;
        }
        if ('curl' === $handler) {
            if (!extension_loaded('curl')) {
                throw new Exception('The cURL extension must be loaded in order to use the "curl" handler.');
            }

            return new SipayCurlHttpClient();
        }

        throw new InvalidArgumentException('The http client handler must be set to "curl" or an instance of Sipay\HttpClients\SipayHttpClientInterface');
    }

    /**
     * Detect default HTTP client.
     *
     * @return SipayHttpClientInterface
     */
    private static function detectDefaultClient()
    {
        return new SipayCurlHttpClient();
    }
}
