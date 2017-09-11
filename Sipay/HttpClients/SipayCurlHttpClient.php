<?php
namespace Sipay\HttpClients;

use Sipay\Http\GraphRawResponse;
use Sipay\Exceptions\SipaySDKException;

/**
 * Class SipayCurlHttpClient
 *
 * @package Sipay
 */
class SipayCurlHttpClient implements SipayHttpClientInterface
{
    /**
     * @var string The client error message
     */
    protected $curlErrorMessage = '';

    /**
     * @var int The curl client error code
     */
    protected $curlErrorCode = 0;

    /**
     * @var string|boolean The raw response from the server
     */
    protected $rawResponse;

    /**
     * @var SipayCurl Procedural curl as object
     */
    protected $sipayCurl;

    /**
     * @param SipayCurl|null Procedural curl as object
     */
    public function __construct(SipayCurl $sipayCurl = null)
    {
        $this->sipayCurl = $sipayCurl ?: new SipayCurl();
    }

    /**
     * @inheritdoc
     */
    public function send($url, $method, $body, array $headers, $timeOut, $verifypeer, $certs)
    {
        $this->openConnection($url, $method, $body, $headers, $timeOut, $verifypeer, $certs);
        $this->sendRequest();

        if ($curlErrorCode = $this->sipayCurl->errno()) {
            throw new SipaySDKException($this->sipayCurl->error(), $curlErrorCode);
        }

        // Separate the raw headers from the raw body
        list($rawHeaders, $rawBody) = $this->extractResponseHeadersAndBody();

        $this->closeConnection();

        return new GraphRawResponse($rawHeaders, $rawBody);
    }

    /**
     * Opens a new curl connection.
     *
     * @param string $url     The endpoint to send the request to.
     * @param string $method  The request method.
     * @param string $body    The body of the request.
     * @param array  $headers The request headers.
     * @param int    $timeOut The timeout in seconds for the request.
     * @param array  $certs   The certs.
     */
    public function openConnection($url, $method, $body, array $headers, $timeOut, $verifypeer, $certs)
    {
        $options = array(
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->compileRequestHeaders($headers),
            CURLOPT_URL => $url,
            CURLOPT_CONNECTTIMEOUT => 10,
        		CURLOPT_SSL_VERIFYPEER => ($verifypeer==1)?TRUE:FALSE,
        		CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => $timeOut,
            CURLOPT_RETURNTRANSFER => true, // Follow 301 redirects
            CURLOPT_SSLCERT => __DIR__ . $certs['ssl'],
            CURLOPT_SSLKEY => __DIR__ . $certs['ssl_key'],
            CURLOPT_CAINFO => __DIR__ . $certs['cacert']
        );

        if ($method !== "GET") {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        }

// if($url != "https://sipayecommerce.sipay.es:10010/api/v1/auth"){
//   // print_r(json_encode($body));die;
//     print_r($url." ".$verifypeer);die;
// }
// print_r($url." ".$verifypeer);die;
// print_r($certs);die;
        $this->sipayCurl->init();
        $this->sipayCurl->setoptArray($options);
    }

    /**
     * Closes an existing curl connection
     */
    public function closeConnection()
    {
        $this->sipayCurl->close();
    }

    /**
     * Send the request and get the raw response from curl
     */
    public function sendRequest()
    {
        $this->rawResponse = $this->sipayCurl->exec();
    }

    /**
     * Compiles the request headers into a curl-friendly format.
     *
     * @param array $headers The request headers.
     *
     * @return array
     */
    public function compileRequestHeaders(array $headers)
    {
        $return = [];

        foreach ($headers as $key => $value) {
            $return[] = $key . ': ' . $value;
        }

        return $return;
    }

    /**
     * Extracts the headers and the body into a two-part array
     *
     * @return array
     */
    public function extractResponseHeadersAndBody()
    {
        $parts = explode("\r\n\r\n", $this->rawResponse);
        $rawBody = array_pop($parts);
        $rawHeaders = implode("\r\n\r\n", $parts);

        return [trim($rawHeaders), trim($rawBody)];
    }
}
