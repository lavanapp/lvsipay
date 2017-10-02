<?php
namespace Sipay\Authentication;

use Sipay\SipayRequest;
use Sipay\SipayClient;

/**
 * Class Authentication
 *
 * @package Sipay
 */
class Auth
{
    /**
     * The Sipay client.
     *
     * @var SipayClient
     */
    protected $client;

    /**
     * The params for authentication.
     *
     * @var array
     */
    protected $params = [];

    /**
     * @const string URL for sandbox authorization conections.
     */
    const SANDBOX_URL = 'https://sandbox.sipayecommerce.sipay.es:10010/api/v1';

    /**
     * @const string URL for staging authorization conections.
     */
    const STAGING_URL = 'https://f23.sipayecommerce.sipay.es:10010/api/v1';

    /**
     * @const string URL for live authorization conections.
     */
    const LIVE_URL = 'https://sipayecommerce.sipay.es:10010/api/v1';

    /**
     * @param Sipaylient $client
     */
    public function __construct(SipayClient $client)
    {
        $this->client = $client;
    }

    /**
     * Returns the base Sipay URL.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return (strtoupper($this->client->getEnvironment()) === "LIVE") ? static::LIVE_URL : ((strtoupper($this->client->getEnvironment()) === "STAGING") ? static::STAGING_URL : static::SANDBOX_URL);
    }

    /**
     * Send a request to the auth endpoint.
     *
     * @param array  $config      An array of parameters to request.
     *
     * @return array
     *
     * @throws SipaySDKException
     */
    public function requestAuthentication($config)
    {
        $config = array_merge(["authtype" => "sslclient",
                            "api.notpage" => "",
                            "api.notmode" => "sync"],$config);

        $response = $this->sendRequestWithClientParams($this->getBaseUrl() . '/auth', $config);

        $objData = serialize( $response);
        $filePath = getcwd().DIRECTORY_SEPARATOR."files".DIRECTORY_SEPARATOR."sipay.txt";
        if (is_writable($filePath)) {
            $fp = fopen($filePath, "w");
            fwrite($fp, $objData);
            fclose($fp);
        }

        $data = $response->getDecodedBody();

        return $data;
    }

    /**
     * Send a request
     *
     * @param string                  $endpoint
     * @param array                   $params
     *
     * @return SipayResponse
     *
     * @throws SipayResponseException
     */
    protected function sendRequestWithClientParams($endpoint, array $params)
    {
        $this->lastRequest = new SipayRequest(
            'POST',
            $endpoint,
            $params
        );

        return $this->client->sendRequest($this->lastRequest, true);
    }

}
