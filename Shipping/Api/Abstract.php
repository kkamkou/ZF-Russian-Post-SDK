<?php
/**
 * Licensed under the MIT License
 * Redistributions of files must retain the copyright notice below.
 *
 * @category ThirdParty
 * @package  Vkontakte
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     https://github.com/kkamkou/ZF-Russian-Post-SDK
 */

namespace shipping;

class ShippingAbstract
{
    /**
    * Object for the http client
    * @var \Zend_Http_Client
    */
    protected $_httpClient;

    /**
    * Constructor
    *
    * @return void
    */
    public function __construct(array $options = array())
    {
        // the http client initialization
        if (isset($options['httpClient'])) {
            $this->setHttpClient($options['httpClient']);
        } else {
            $this->setHttpClient($this->getDefaultHttpClient());
        }
    }

    /**
    * Changes the HTTP client
    *
    * @return \shipping\ShippingAbstract
    */
    public function setHttpClient(\Zend_Http_Client $client)
    {
        $this->_httpClient = $client;
        return $this;
    }

    /**
    * Returns current HTTP client
    *
    * @return \Zend_Http_Client
    */
    public function getHttpClient()
    {
        return $this->_httpClient;
    }

    /**
    * Returns object for the HTTP client
    *
    * @return \Zend_Http_Client
    */
    public function getDefaultHttpClient()
    {
        $client = new \Zend_Http_Client();
        $client->setConfig(
            array(
                'storeresponse'   => true,
                'strictredirects' => true,
                'timeout'         => 10,
                'useragent'       => 'ZF-Shipping-Api'
            )
        );

        return $client;
    }

    /**
    * Makes request and checks the result
    *
    * @param  string $uri
    * @param  array  $params (Default: array)
    * @return stdClass
    * @throws \Exception when the result was unsuccessful
    */
    protected function _request(\Zend_Http_Client $client)
    {
        // let's send request and check what we have
        try {
            $response = $client->request();
        } catch (\Zend_Http_Client_Exception $e) {
            throw new \Exception('Client request was unsuccessful', $e->getCode(), $e);
        }

        if (!$response->isSuccessful()) {
            throw new \Exception(
                "Request failed({$response->getStatus()}): {$response->getMessage()} at " .
                    $this->_httpClient->getLastRequest()
            );
        }

        // the response has JSON format, we should decode it
        $decoded = \Zend_Json::decode($response->getBody(), \Zend_Json::TYPE_OBJECT);
        if ($decoded === null) {
            throw new \UnexpectedValueException(
                'Response is not JSON: ' . $response->getBody()
            );
        }

        return $decoded;
    }
}
