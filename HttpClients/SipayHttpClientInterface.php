<?php
namespace Lvsipay\HttpClients;

/**
 * Interface SipayHttpClientInterface
 *
 * @package Sipay
 */
interface SipayHttpClientInterface
{
    /**
     * Sends a request to the server and returns the raw response.
     *
     * @param string $url     The endpoint to send the request to.
     * @param string $method  The request method.
     * @param string $body    The body of the request.
     * @param array  $headers The request headers.
     * @param int    $timeOut The timeout in seconds for the request.
     * @param array  $certs   The certs
     *
     * @return \Sipay\Http\GraphRawResponse Raw response from the server.
     *
     * @throws \Sipay\Exceptions\SipaySDKException
     */
    public function send($url, $method, $body, array $headers, $timeOut, $verifypeer, $certs);
}
