<?php
namespace Lvsipay;

use Lvsipay\Exceptions\SipaySDKException;

/**
 * Class Request
 *
 * @package Sipay
 */
class SipayRequest
{
    /**
     * @var string The HTTP method for this request.
     */
    protected $method;

    /**
     * @var string The endpoint for this request.
     */
    protected $endpoint;

    /**
     * @var array The headers to send with this request.
     */
    protected $headers = [];

    /**
     * @var array The parameters to send with this request.
     */
    protected $params = [];

    /**
     * Creates a new Request entity.
     *
     * @param string|null             $method
     * @param string|null             $endpoint
     * @param array|null              $params
     */
    public function __construct($method = null, $endpoint = null, array $params = [])
    {
        $this->setMethod($method);
        $this->setEndpoint($endpoint);
        $this->setParams($params);
    }

    /**
     * Set the HTTP method for this request.
     *
     * @param string
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
    }

    /**
     * Return the HTTP method for this request.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Validate that the HTTP method is set.
     *
     * @throws SipaySDKException
     */
    public function validateMethod()
    {
        if (!$this->method) {
            throw new SipaySDKException('HTTP method not specified.');
        }

        if (!in_array($this->method, ['GET', 'POST', 'DELETE'])) {
            throw new SipaySDKException('Invalid HTTP method specified.');
        }
    }

    /**
     * Set the endpoint for this request.
     *
     * @param string
     *
     * @return SipayRequest
     *
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Return the endpoint for this request.
     *
     * @return string
     */
    public function getEndpoint()
    {
        // For batch requests, this will be empty
        return $this->endpoint;
    }

    /**
     * Return the headers for this request.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Set the headers for this request.
     *
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * Set the params for this request.
     *
     * @param array $params
     *
     * @return SipayRequest
     *
     * @throws SipaySDKException
     */
    public function setParams(array $params = [])
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Returns the body of the request as URL-encoded.
     *
     * @return array
     */
    public function getBody()
    {
        return $this->getPostParams();
    }

    /**
     * Generate and return the params for this request.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Only return params on POST requests.
     *
     * @return array
     */
    public function getPostParams()
    {
        if ($this->getMethod() != 'GET') {
            return $this->getParams();
        }

        return [];
    }

    /**
     * Generate and return the URL for this request.
     *
     * @return string
     */
    public function getUrl()
    {
        $this->validateMethod();

        return $this->getEndpoint();
    }

}
