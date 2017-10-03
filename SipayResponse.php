<?php
namespace Sipay;

use Sipay\Exceptions\SipayResponseException;
use Sipay\Exceptions\SipaySDKException;

/**
 * Class SipayResponse
 *
 * @package Sipay
 */
class SipayResponse
{
    /**
     * @var int The HTTP status code response.
     */
    protected $httpStatusCode;

    /**
     * @var array The headers returned.
     */
    protected $headers;

    /**
     * @var string The raw body of the response.
     */
    protected $body;

    /**
     * @var array The decoded body of the response.
     */
    protected $decodedBody = [];

    /**
     * @var SipayRequest The original request that returned this response.
     */
    protected $request;

    /**
     * @var SipaySDKException The exception thrown by this request.
     */
    protected $thrownException;

    /**
     * Creates a new Response entity.
     *
     * @param SipayRequest $request
     * @param string|null     $body
     * @param int|null        $httpStatusCode
     * @param array|null      $headers
     */
    public function __construct(SipayRequest $request, $body = null, $httpStatusCode = null, array $headers = [])
    {
        $this->request = $request;
        $this->body = $body;
        $this->httpStatusCode = $httpStatusCode;
        $this->headers = $headers;
        
        $this->decodeBody();
    }

    /**
     * Return the original request that returned this response.
     *
     * @return SipayRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Return the HTTP status code for this response.
     *
     * @return int
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    /**
     * Return the HTTP headers for this response.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Return the raw body response.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Return the decoded body response.
     *
     * @return array
     */
    public function getDecodedBody()
    {
        return $this->decodedBody;
    }

    /**
     * Returns true if returned an error message.
     *
     * @return boolean
     */
    public function isError()
    {
        return isset($this->decodedBody['error']);
    }

    /**
     * Throws the exception.
     *
     * @throws SipaySDKException
     */
    public function throwException()
    {
        throw $this->thrownException;
    }

    /**
     * Instantiates an exception to be thrown later.
     */
    public function makeException()
    {
        $this->thrownException = SipayResponseException::create($this);
    }

    /**
     * Returns the exception that was thrown for this request.
     *
     * @return SipayResponseException|null
     */
    public function getThrownException()
    {
        return $this->thrownException;
    }

    /**
     * Convert the raw response into an array if possible.
     *
     * Graph will return 2 types of responses:
     * - JSON(P)
     *    Most responses from Graph are JSON(P)
     * - application/x-www-form-urlencoded key/value pairs
     *    Happens on the `/oauth/access_token` endpoint when exchanging
     *    a short-lived access token for a long-lived access token
     * - And sometimes nothing :/ but that'd be a bug.
     */
    public function decodeBody()
    {
        $this->decodedBody = json_decode($this->body, true);

        if ($this->decodedBody === null) {
            $this->decodedBody = [];
            parse_str($this->body, $this->decodedBody);
        } elseif (is_bool($this->decodedBody)) {
            // Backwards compatibility for Graph < 2.1.
            // Mimics 2.1 responses.
            // @TODO Remove this after Graph 2.0 is no longer supported
            $this->decodedBody = ['success' => $this->decodedBody];
        } elseif (is_numeric($this->decodedBody)) {
            $this->decodedBody = ['id' => $this->decodedBody];
        }

        if (!is_array($this->decodedBody)) {
            $this->decodedBody = [];
        }

        if ($this->isError()) {
            $this->makeException();
        }
    }
}
