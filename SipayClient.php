<?php
namespace Lvsipay;

use Lvsipay\HttpClients\SipayHttpClientInterface;
use Lvsipay\HttpClients\SipayCurlHttpClient;
use Lvsipay\Exceptions\SipaySDKException;

/**
 * Class SipayClient
 *
 * @package Sipay
 */
class SipayClient
{
    /**
     * @var SipayHttpClientInterface HTTP client handler.
     */
    protected $httpClientHandler;

    /**
     * @var string Environment.
     */
    protected $environment;

    /**
     * @var array Certificates.
     */
    protected $certs;

    /**
     * @const string Path where the certificate is found
     */
    const SANDBOX_SSL_CERT = '/certs/sandbox/E-Commerce.cliente.LAVANAPP_1.pem';

    /**
     * @const string Path where the certificate key is found
     */
    const SANDBOX_SSL_KEY = '/certs/sandbox/E-Commerce.cliente.LAVANAPP.privkey.pem';

    /**
     * @const string Path where the CA certificate is found
     */
    const SANDBOX_CACERT = '/certs/sandbox/CA_Sipay_DEV.pem';

    /**
     * @const string Path where the certificate is found
     */
    const STAGING_SSL_CERT = '/certs/staging/E-Commerce.cliente.LAVANAPP.pem';

    /**
     * @const string Path where the certificate key is found
     */
    const STAGING_SSL_KEY = '/certs/staging/E-Commerce.cliente.LAVANAPP.privkey.pem';

    /**
     * @const string Path where the CA certificate is found
     */
    const STAGING_CACERT = '/certs/staging/CA_Sipay_SHA256.pem';

    /**
     * @const string Path where the certificate is found
     */
    const LIVE_SSL_CERT = '/certs/live/E-Commerce.cliente.LAVANAPP_1.pem';

    /**
     * @const string Path where the certificate key is found
     */
    const LIVE_SSL_KEY = '/certs/live/E-Commerce.cliente.LAVANAPP.privkey.pem';

    /**
     * @const string Path where the CA certificate is found
     */
    const LIVE_CACERT = '/certs/live/CA_Sipay_SHA256.pem';

    /**
     * @const int The timeout in seconds for a normal request.
     */
    const DEFAULT_REQUEST_TIMEOUT = 60;

    /**
     * Instantiates a new SipayClient object.
     *
     * @param SipayHttpClientInterface|null $httpClientHandler
     * @param string                        $environment
     */
    public function __construct(SipayHttpClientInterface $httpClientHandler = null, $environment = null)
    {
        $this->httpClientHandler = $httpClientHandler ?: $this->detectHttpClientHandler();
        if(!$this->checkEnvironment($environment)) throw new SipaySDKException('Invalid environment');
        $this->environment = $environment;
        $this->certs = $this->getCerts();
    }

    /**
     * Returns if it's a valid environment
     *
     * @return array
     */
    public function checkEnvironment($environment)
    {
        return (strtoupper($environment) === "LIVE" || (strtoupper($environment) === "STAGING") || (strtoupper($environment) === "SANDBOX") );
    }

    /**
     * Returns certificates depending on the environment
     *
     * @return array
     */
    public function getCerts()
    {
        $certs = [];
        if (strtoupper($this->getEnvironment()) === "LIVE") {
          $certs = ["ssl" => static::LIVE_SSL_CERT, "ssl_key" => static::LIVE_SSL_KEY, "cacert" => static::LIVE_CACERT];
        }elseif(strtoupper($this->getEnvironment()) === "STAGING"){
          $certs = ["ssl" => static::STAGING_SSL_CERT, "ssl_key" => static::STAGING_SSL_KEY, "cacert" => static::STAGING_CACERT];
        }else{
          $certs = ["ssl" => static::SANDBOX_SSL_CERT, "ssl_key" => static::SANDBOX_SSL_KEY, "cacert" => static::SANDBOX_CACERT];
        }
        return $certs;
    }

    /**
     * Sets the HTTP client handler.
     *
     * @param SipayHttpClientInterface $httpClientHandler
     */
    public function setHttpClientHandler(SipayHttpClientInterface $httpClientHandler)
    {
        $this->httpClientHandler = $httpClientHandler;
    }

    /**
     * Returns the HTTP client handler.
     *
     * @return SipayHttpClientInterface
     */
    public function getHttpClientHandler()
    {
        return $this->httpClientHandler;
    }

    /**
     * Returns the environment.
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Detects which HTTP client handler to use.
     *
     * @return SipayHttpClientInterface
     */
    public function detectHttpClientHandler()
    {
        return new SipayCurlHttpClient();
    }

    /**
     * Prepares the request for sending to the client handler.
     *
     * @param SipayRequest $request
     *
     * @return array
     */
    public function prepareRequestMessage(SipayRequest $request)
    {
        $url = $request->getUrl();

        $request->setHeaders([
            'Content-Type' => 'application/json',
        ]);
// if($url!="https://sandbox.sipayecommerce.sipay.es:10010/api/v1/auth"){print_r($url." ".json_encode($request->getHeaders()));die;}
        return [
            $url,
            $request->getMethod(),
            $request->getHeaders(),
            $request->getBody()
        ];
    }

    /**
     * Makes the request and returns the result.
     *
     * @param SipayRequest $request
     *
     * @return SipayResponse
     *
     * @throws SipaySDKException
     */
    public function sendRequest(SipayRequest $request, $verifypeer = true)
    {
        list($url, $method, $headers, $body) = $this->prepareRequestMessage($request);

        $timeOut = static::DEFAULT_REQUEST_TIMEOUT;

        // Should throw `SipaySDKException` exception on HTTP client error.
        // Don't catch to allow it to bubble up.
        $rawResponse = $this->httpClientHandler->send($url, $method, $body, $headers, $timeOut, $verifypeer, $this->certs);

        $returnResponse = new SipayResponse(
            $request,
            $rawResponse->getBody(),
            $rawResponse->getHttpResponseCode(),
            $rawResponse->getHeaders()
        );

        if ($returnResponse->isError()) {
            throw $returnResponse->getThrownException();
        }
// if($url != "https://sandbox.sipayecommerce.sipay.es:10010/api/v1/auth"){print_r($returnResponse);die;}
// if($url != "https://f23.sipayecommerce.sipay.es:10010/api/v1/auth"){print_r($returnResponse);die;}

        return $returnResponse;
    }

}
