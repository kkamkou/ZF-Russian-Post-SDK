<?php
/**
 * Licensed under the MIT License
 * Redistributions of files must retain the copyright notice below.
 *
 * @category ThirdParty
 * @package  Shipping
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
    * Stores the last error message
    * @var string
    */
    protected $_lastError;

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
            $this->setHttpClient($this->_getDefaultHttpClient());
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
    * Stores the error description
    *
    * @return \shipping\ShippingAbstract
    */
    public function setError($msg)
    {
        $this->_lastError = $msg;
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
    * Returns last error during request
    *
    * @return string
    */
    public function getError()
    {
        return $this->_lastError;
    }

    /**
    * Returns information about the latest request. Was it successful
    *
    * @return bool
    */
    public function hasError()
    {
        return !empty($this->_lastError);
    }

    /**
    * Returns object for the HTTP client
    *
    * @return \Zend_Http_Client
    */
    protected function _getDefaultHttpClient()
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
    * @param  \Zend_Http_Client $client
    * @return \Zend_Http_Response
    * @throws \Exception when the result was unsuccessful
    */
    protected function _request(\Zend_Http_Client $client)
    {
        // we should reset the error holder
        $this->_lastError = null;

        // let's send request and check what we have
        try {
            $response = $client->request();
        } catch (\Zend_Http_Client_Exception $e) {
            throw new \Exception(
                'Client request was unsuccessful', $e->getCode(), $e
            );
        }

        // HTTP headers check
        if (!$response->isSuccessful()) {
            throw new \Exception(
                "Request failed({$response->getStatus()}): {$response->getMessage()} at " .
                    $this->_httpClient->getLastRequest()
            );
        }

        // default output
        return $response;
    }
}
