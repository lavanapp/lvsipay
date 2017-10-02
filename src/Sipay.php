<?php
namespace Sipay;

use Sipay\Authentication\Auth;
use Sipay\HttpClients\HttpClientsFactory;
use Sipay\Exceptions\SipaySDKException;
use Sipay\Url\SipayUrlManipulator;

/**
 * Class Sipay
 *
 * @package Sipay
 */
class Sipay
{
    /**
     * @var SipayClient The Sipay client service.
     */
    protected $client;

    /**
     * @var string The merchant id.
     */
    protected $merchantid;

    /**
     * @var string The merchant name.
     */
    protected $merchantname;

    /**
     * @var string The currency.
     */
    protected $currency;

    /**
     * @const string Default currency for requests.
     */
    const DEFAULT_CURRENCY = '978';

    /**
     * @var string The language.
     */
    protected $lang;

    /**
     * @const string Default lang for requests.
     */
    const DEFAULT_LANG = '0';

    /**
     * @var Authentication The request id authentication.
     */
    protected $authenticate;

    /**
     * @var SipayResponse|null Stores the last request made
     */
    protected $lastResponse;

    /**
     * @const string Identification key for Api.
     */
    const APIKEY = '123456789';

    /**
     * @const string URL for sandbox conections.
     */
    const SANDBOX_URL = 'https://sandbox.sipayecommerce.sipay.es/api/v1';

    /**
     * @const string URL for staging conections.
     */
    const STAGING_URL = 'https://f23.sipayecommerce.sipay.es/api/v1';

    /**
     * @const string URL for live conections.
     */
    const LIVE_URL = 'https://sipayecommerce.sipay.es/api/v1';

    /**
     * @const string Resource for tokenizations storages
     */
    const RESOURCE_TOKENIZATIONS_STORAGES = 'tokenizations/storages';

    /**
     * @const string Resource for tokenizations payments
     */
    const RESOURCE_TOKENIZATIONS_PAYMENTS = 'tokenizations/payments';

    /**
     * @const string Resource for tokenizations payments
     */
    const RESOURCE_TOKENIZATIONS_REFUNDS = 'tokenizations/refunds';

    /**
     * @const string Resource for payments
     */
    const RESOURCE_PAYMENTS = 'payments';

    /**
     * @const string Resource for refunds at no stored method
     */
    const RESOURCE_REFUNDS = 'refunds';

    /**
     * @const string Resource for refunds by id
     */
    const RESOURCE_REFUNDS_BYID = 'refundsbyid';

    /**
     * @const string Cancelation for an action
     */
    const RESOURCE_CANCELATIONS = 'cancelations';

    /**
     * Instantiates a new Sipay super-class object.
     *
     * @param array $config
     *
     * @throws SipaySDKException
     */
    public function __construct(array $config = [])
    {
        $config = array_merge([
            'http_client_handler' => null
        ], $config);

        if (!$config['merchantid']) {
            throw new SipaySDKException('Required "merchantid" key not supplied in config and could not find fallback environment variable');
        }
        if (!$config['merchantname']) {
            throw new SipaySDKException('Required "merchantname" key not supplied in config and could not find fallback environment variable');
        }
        $this->merchantid = $config['merchantid'];
        $this->merchantname = $config['merchantname'];
        $this->currency = isset($config['currency'])?$config['currency']:static::DEFAULT_CURRENCY;
        $this->lang = isset($config['lang'])?$config['lang']:static::DEFAULT_LANG;
        $this->client = new SipayClient(
            HttpClientsFactory::createHttpClient($config['http_client_handler']),
            $config['environment']
        );
        $this->authenticate = new Auth($this->client);
    }

    /**
     * Returns the SipayClient service.
     *
     * @return SipayClient
     */
    public function getClient()
    {
        return $this->client;
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
     * Save card into Sipay vault
     *
     * @param array                  $params
     *
     * @param string                 $type
     *
     * @return SipayResponse
     */
    public function tokenization_storages($params,$method)
    {
        $config = ["apikey" => static::APIKEY,
                  "resource" => self::RESOURCE_TOKENIZATIONS_STORAGES,
                  "ticket" => "",
                  "amount" => ""];
        $auth = static::getAuthentication($config);
// print_r($auth);die();
        if(isset($auth['idstorage'])){
          $endpoint = $this->getBaseUrl() . SipayUrlManipulator::forceSlashPrefix(self::RESOURCE_TOKENIZATIONS_STORAGES);

          switch (strtoupper($method)) {
            case 'POST':
              $headers = [];
              $data = ["apikey" => static::APIKEY,
                          "authtype" => "sslclient",
                          "lang" => $this->lang,
                          "merchantid" => $this->merchantid,
                          "merchantname" => $this->merchantname,
                          "idstorage" => $auth['idstorage'],
                          "pan" => $params['pan'],
                          "expiration" => $params['expiration'],
                          "cardholdername" => $params['cardholdername'],
                          "cardindex" => $params['cardindex'],
                          // If you want to store the payment method without placing an order, make a sale of 1 euro on the customer's card and after a refund of 1 euros.
                          // If the first operation was accepted, the card will be stored.
                          "tokenizations.checkmode" => ($params['order_id']!="")?"":"mode1",
                          "tokenizations.ticket" =>  ($params['order_id']!="")?:$params['card_id']];
              break;
            case 'DELETE':
              $headers = [];
              $data = ["apikey" => static::APIKEY,
                          "authtype" => "sslclient",
                          "lang" => $this->lang,
                          "merchantid" => $this->merchantid,
                          "merchantname" => $this->merchantname,
                          "idstorage" => $auth['idstorage'],
                          "cardindex" => $params['cardindex']];
              break;
            case 'GET':
              $data = [];
              $headers = ["Accept" => "application/json",
                          "X-Sipay-API-v1-idstorage" => $auth['idstorage'],
                          "X-Sipay-API-v1-merchantid" => $this->merchantid,
                          "X-Sipay-API-v1-merchantname" => $this->merchantname,
                          "X-Sipay-API-v1-authtype" => "sslclient",
                          "X-Sipay-API-v1-lang" => $this->lang];
              $endpoint .= SipayUrlManipulator::forceSlashPrefix($params['cardindex']);
              break;

            default:
              # code...
              break;
          }

          $response = $this->sendRequest(
              strtoupper($method),
              $endpoint,
              $data,
              $headers
          );

          $objData = serialize( $response);
          $filePath = getcwd().DIRECTORY_SEPARATOR."files".DIRECTORY_SEPARATOR."sipay.txt";
          if (is_writable($filePath)) {
              $fp = fopen($filePath, "w");
              fwrite($fp, $objData);
              fclose($fp);
          }

          return $response->getDecodedBody();
        }else{ // no se ha realizado correctamente la autorización
          return $auth;
        }
    }

    /**
     * Make a payment with a stored payment method
     *
     * @param array                  $params
     *
     * @return SipayResponse
     */
    public function tokenization_payments($params)
    {
        $config = ["resource" => self::RESOURCE_TOKENIZATIONS_PAYMENTS,
                  "ticket" => $params['code'],
                  "amount" => $params['amount'],
                  "api.notpage" => "",
                  "api.notmode" => "",
                  "api.dstpage" => "",
                  "reference" => ""];

        $auth = static::getAuthentication($config);

        if(isset($auth['idrequest'])){
          $data = ["authtype" => "sslclient",
                      "lang" => $this->lang,
                      "currency" => $this->currency,
                      "merchantid" => $this->merchantid,
                      "merchantname" => $this->merchantname,
                      "idrequest" => $auth['idrequest'],
                      "cardindex" => $params['cardindex'],
                      "amount" => $params['amount'],
                      "ticket" => $params['code'],
                      "reference" => ""];

          $response = $this->post($this->getBaseUrl() . SipayUrlManipulator::forceSlashPrefix(self::RESOURCE_TOKENIZATIONS_PAYMENTS), $data);

          $objData = serialize( $response);
          $filePath = getcwd().DIRECTORY_SEPARATOR."files".DIRECTORY_SEPARATOR."sipay.txt";
          if (is_writable($filePath)) {
              $fp = fopen($filePath, "w");
              fwrite($fp, $objData);
              fclose($fp);
          }

          return $response->getDecodedBody();
        }else{ // no se ha realizado correctamente la autorización
          return $auth;
        }
    }

    /**
     * Make a payment with a stored payment method
     *
     * @param array                  $params
     *
     * @return SipayResponse
     */
    public function tokenization_refunds($params)
    {
        $config = ["resource" => self::RESOURCE_TOKENIZATIONS_REFUNDS,
                  "ticket" => $params['code'],
                  "amount" => $params['amount'],
                  "api.notpage" => "",
                  "api.notmode" => "",
                  "reference" => ""];

        $auth = static::getAuthentication($config);

        if(isset($auth['idrefund'])){
          $data = ["authtype" => "sslclient",
                      "lang" => $this->lang,
                      "currency" => $this->currency,
                      "merchantid" => $this->merchantid,
                      "merchantname" => $this->merchantname,
                      "idrequest" => $auth['idrefund'],
                      "cardindex" => $params['cardindex'],
                      "amount" => $params['amount'],
                      "ticket" => $params['code'],
                      "reference" => "",
                      "transaction_id" => $params['transaction_id']];

          $response = $this->post($this->getBaseUrl() . SipayUrlManipulator::forceSlashPrefix(self::RESOURCE_TOKENIZATIONS_REFUNDS), $data);

          $objData = serialize( $response);
          $filePath = getcwd().DIRECTORY_SEPARATOR."files".DIRECTORY_SEPARATOR."sipay.txt";
          if (is_writable($filePath)) {
              $fp = fopen($filePath, "w");
              fwrite($fp, $objData);
              fclose($fp);
          }


          return $response->getDecodedBody();
        }else{
          return $auth;
        }
    }

    /**
     * Make a payment with an unsaved card
     *
     * @param array                  $params
     *
     * @return SipayResponse
     */
    public function payments($params)
    {
        $config = ["resource" => self::RESOURCE_PAYMENTS,
                  "ticket" => $params['code'],
                  "amount" => $params['amount'],
                  "api.notpage" => "",
                  "api.notmode" => "",
                  "api.dstpage" => "",
                  "reference" => ""];

        $auth = static::getAuthentication($config);

        if(isset($auth['idrequest'])){
          $data = ["authtype" => "sslclient",
                      "lang" => $this->lang,
                      "currency" => $this->currency,
                      "merchantid" => $this->merchantid,
                      "merchantname" => $this->merchantname,
                      "idrequest" => $auth['idrequest'],
                      "amount" => $params['amount'],
                      "ticket" => $params['code'],
                      "pan" => $params['pan'],
                      "expiration" => $params['expiration'],
                      "cardholdername" => $params['cardholdername'],
                      "cvv" => $params['cvv'],
                      "reference" => "",
                      "customfield1" => "",
                      "customfield2" => ""];

          $response = $this->post($this->getBaseUrl() . SipayUrlManipulator::forceSlashPrefix(self::RESOURCE_PAYMENTS), $data);

          $objData = serialize( $response);
          $filePath = getcwd().DIRECTORY_SEPARATOR."files".DIRECTORY_SEPARATOR."sipay.txt";
          if (is_writable($filePath)) {
              $fp = fopen($filePath, "w");
              fwrite($fp, $objData);
              fclose($fp);
          }

          return $response->getDecodedBody();
        }else{ // no se ha realizado correctamente la autorización
          return $auth;
        }
    }

    /**
     * Make a refund with on an unsaved card
     *
     * @param array                  $params
     *
     * @return SipayResponse
     */
    public function refunds($params)
    {
        $config = ["apikey" => static::APIKEY,
                  "resource" => self::RESOURCE_REFUNDS,
                  "ticket" => $params['code'],
                  "amount" => $params['amount'],
                  "api.notpage" => "",
                  "api.notmode" => "",
                  "api.dstpage" => "",
                  "reference" => ""];

        $auth = static::getAuthentication($config);

        if(isset($auth['idrefund'])){
          $data = ["authtype" => "sslclient",
                      "lang" => $this->lang,
                      "currency" => $this->currency,
                      "merchantid" => $this->merchantid,
                      "merchantname" => $this->merchantname,
                      "idrefund" => $auth['idrefund'],
                      "amount" => $params['amount'],
                      "ticket" => $params['code'],
                      "pan" => $params['pan'],
                      "expiration" => $params['expiration'],
                      "transaction_id" => $params['transaction_id']];

          $response = $this->post($this->getBaseUrl() . SipayUrlManipulator::forceSlashPrefix(self::RESOURCE_REFUNDS), $data);

          $objData = serialize( $response);
          $filePath = getcwd().DIRECTORY_SEPARATOR."files".DIRECTORY_SEPARATOR."sipay.txt";
          if (is_writable($filePath)) {
              $fp = fopen($filePath, "w");
              fwrite($fp, $objData);
              fclose($fp);
          }

          return $response->getDecodedBody();
        }else{ // no se ha realizado correctamente la autorización
          return $auth;
        }
    }

    /**
     * Make a refund by id
     *
     * @param array                  $params
     *
     * @return SipayResponse
     */
    public function refunds_byid($params)
    {
        $config = ["apikey" => static::APIKEY,
                  "resource" => self::RESOURCE_REFUNDS,
                  "ticket" => $params['code'],
                  "amount" => $params['amount'],
                  "api.notpage" => "",
                  "api.notmode" => "",
                  "api.dstpage" => "",
                  "reference" => ""];

        $auth = static::getAuthentication($config);

        if(isset($auth['idrefund'])){
          $data = ["authtype" => "sslclient",
                      "lang" => $this->lang,
                      "currency" => $this->currency,
                      "merchantid" => $this->merchantid,
                      "merchantname" => $this->merchantname,
                      "idrequest" => $auth['idrefund'],
                      "amount" => $params['amount'],
                      "ticket" => $params['code'],
                      "idoriginalrequest" => $params['transaction_id']];

          $response = $this->post($this->getBaseUrl() . SipayUrlManipulator::forceSlashPrefix(self::RESOURCE_REFUNDS_BYID), $data);

          $objData = serialize( $response);
          $filePath = getcwd().DIRECTORY_SEPARATOR."files".DIRECTORY_SEPARATOR."sipay.txt";
          if (is_writable($filePath)) {
              $fp = fopen($filePath, "w");
              fwrite($fp, $objData);
              fclose($fp);
          }

          return $response->getDecodedBody();
        }else{ // no se ha realizado correctamente la autorización
          return $auth;
        }
    }

    /**
     * Make a cancelation
     *
     * @param array                  $params
     *
     * @return SipayResponse
     */
    public function cancelations($params)
    {
        $config = ["apikey" => static::APIKEY,
                  "resource" => self::RESOURCE_CANCELATIONS,
                  "ticket" => $params['code'],
                  "amount" => $params['amount'],
                  "api.notpage" => "",
                  "api.notmode" => ""];

        $auth = static::getAuthentication($config);

        if(isset($auth['idcancelation'])){
          $data = ["authtype" => "sslclient",
                      "merchantid" => $this->merchantid,
                      "merchantname" => $this->merchantname,
                      "idcancelation" => $auth['idcancelation'],
                      "transactionid" => $params['transaction_id']];

          $response = $this->post($this->getBaseUrl() . SipayUrlManipulator::forceSlashPrefix(self::RESOURCE_CANCELATIONS), $data);

          $objData = serialize( $response);
          $filePath = getcwd().DIRECTORY_SEPARATOR."files".DIRECTORY_SEPARATOR."sipay.txt";
          if (is_writable($filePath)) {
              $fp = fopen($filePath, "w");
              fwrite($fp, $objData);
              fclose($fp);
          }

          return $response->getDecodedBody();
        }else{ // no se ha realizado correctamente la autorización
          return $auth;
        }
    }

    /**
     * Returns the request authentication.
     *
     * @param array                  $config
     *
     * @return array
     */
    public function getAuthentication($config)
    {
      $config = array_merge(["merchantid" => $this->merchantid,
              "lang" => $this->lang,
              "currency" => $this->currency], $config);
        $authentication = $this->authenticate;
        return $authentication->requestAuthentication($config);
    }

    /**
     * Sends a GET request and returns the result.
     *
     * @param string                  $endpoint
     *
     * @return SipayResponse
     *
     * @throws SipaySDKException
     */
    public function get($endpoint)
    {
        return $this->sendRequest(
            'GET',
            $endpoint,
            $params = []
        );
    }

    /**
     * Sends a POST request and returns the result.
     *
     * @param string                  $endpoint
     * @param array                   $params
     *
     * @return SipayResponse
     *
     * @throws SipaySDKException
     */
    public function post($endpoint, array $params = [])
    {
        return $this->sendRequest(
            'POST',
            $endpoint,
            $params
        );
    }

    /**
     * Sends a DELETE request and returns the result.
     *
     * @param string                  $endpoint
     * @param array                   $params
     *
     * @return SipayResponse
     *
     * @throws SipaySDKException
     */
    public function delete($endpoint, array $params = [])
    {
        return $this->sendRequest(
            'DELETE',
            $endpoint,
            $params
        );
    }

    /**
     * Sends a request and returns the result.
     *
     * @param string                  $method
     * @param string                  $endpoint
     * @param array                   $params
     *
     * @return SipayResponse
     *
     * @throws SipaySDKException
     */
    public function sendRequest($method, $endpoint, array $params = [], array $headers = [])
    {
        $request = $this->request($method, $endpoint, $params);
        $request->setHeaders($headers);

        return $this->lastResponse = $this->client->sendRequest($request, false);
    }

    /**
     * Instantiates a new SipayRequest entity.
     *
     * @param string                  $method
     * @param string                  $endpoint
     * @param array                   $params
     *
     * @return SipayRequest
     *
     * @throws SipaySDKException
     */
    public function request($method, $endpoint, array $params = [])
    {
        return new SipayRequest(
            $method,
            $endpoint,
            $params
        );
    }

}
