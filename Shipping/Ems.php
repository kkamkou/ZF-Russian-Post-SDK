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

require_once 'Api/Abstract.php';

/**
* Api for the EMS post service, that uses Zend Framework
*/
class Ems extends \shipping\ShippingAbstract
{
    // uri for the EMS service
    const SERVICE_URI = 'http://emspost.ru/api/rest/';

    // types for the international package
    const TYPE_DOC = 'doc';
    const TYPE_ATT = 'att';

    /**
    * Returns filtered set of regions
    *
    * @return array|false
    */
    public function getRegions()
    {
        return $this->_filterRawLocations('regions');
    }

    /**
    * Returns filtered set of cities
    *
    * @return array|false
    */
    public function getCities()
    {
        return $this->_filterRawLocations('cities');
    }

    /**
    * Returns filtered set of countries
    *
    * @return array|false
    */
    public function getCountries()
    {
        return $this->_filterRawLocations('countries');
    }

    /**
    * Calculates the cost and delivery time
    *
    * @param  string $from
    * @param  string $to
    * @param  float  $weight
    * @param  string $type (Default: null)
    * @return array|false
    */
    public function calculate($from, $to, $weight, $type = null)
    {
        // type validation
        if (preg_match('~^[A-Z]{2}$~', $to)
            && !in_array($type, array(self::TYPE_ATT, self::TYPE_DOC))) {
            throw new \UnexpectedValueException(
                'International package requires type to be specified'
            );
        }

        // the weight normalization
        if (strlen($weight) > 1) {
            $weight = $this->_getWeight($weight);
        }

        // http client params
        $client = $this->_getHttpClient()
            ->setParameterGet('method', 'ems.calculate')
            ->setParameterGet('from', $from)
            ->setParameterGet('to', $to)
            ->setParameterGet('weight', $weight);

        // type for the international package
        if (null !== $type) {
            $client->setParameterGet('type', $type);
        }

        // stdClass object
        $results = $this->_request($client);

        // results
        return $this->hasError() ? false : (array)$results->rsp;
    }

    /**
    * Returns set of objects with the region data.
    * Available: "cities", "regions", "countries" or "russia"
    *
    * @param  string $type (Default: russia)
    * @return array|false
    */
    public function getRawLocations($type = 'russia')
    {
        $client = $this->_getHttpClient()
            ->setParameterGet('method', 'ems.get.locations')
            ->setParameterGet('type', $type);

        // stdClass object
        $results = $this->_request($client);

        // results
        return $this->hasError() ? false : (array)$results->rsp->locations;
    }

    /**
    * Returns the max weight of a package
    *
    * @return float|false
    */
    public function getMaxWeight()
    {
        $client = $this->_getHttpClient()
            ->setParameterGet('method', 'ems.get.max.weight');

            // stdClass object
        $results = $this->_request($client);

        // results
        return $this->hasError() ? false : (float)$results->rsp->max_weight;
    }

    /**
    * The weight normalization
    *
    * @param  int $weight
    * @return float|int
    */
    protected function _getWeight($weight)
    {
        switch (true) {
            case $weight < 101:
                return 0.1;
            case $weight < 501:
                return 0.5;
            case $weight < 1001:
                return 1;
            case $weight < 1501:
                return 1.5;
            default:
                return intval($weight / 1000);
        }
    }

    /**
    * Returns current HTTP client
    *
    * @return \Zend_Http_Client
    */
    protected function _getHttpClient()
    {
        return parent::getHttpClient()->resetParameters(true)
            ->setUri(self::SERVICE_URI);
    }

    /**
    * Returns filtered set of locations
    *
    * @param  string $type
    * @return array|false
    */
    protected function _filterRawLocations($type)
    {
        // the locations set
        $locations = $this->getRawLocations($type);
        if (!$locations) {
            return false;
        }

        // normalization
        $set = array();
        foreach ($locations as $location) {
            if ($location->type == $type) {
                $set[$location->name] = $location->value;
            }
        }
        return $set;
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
        // the response body
        $body = parent::_request($client)->getBody();

        // the response has JSON format, we should decode it
        $decoded = \Zend_Json::decode($body, \Zend_Json::TYPE_OBJECT);
        if (null === $decoded) {
            throw new \UnexpectedValueException('Response is not JSON: ' . $body);
        }

        // do we have errors?
        if (isset($decoded->rsp->err)) {
            $this->setError(
                $decoded->rsp->err->code . ': ' . $decoded->rsp->err->msg
            );
        }

        // results
        return $decoded;
    }
}
