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
*
* Example:
*  $ems = new \shipping\Ems();
*
*  $from = 'city--moskva';
*  $target = 'city--astrahan';
*  $weight = floatval(1200 / 1000);
*  $country = 'US';
*
*  var_dump($ems->calculate($from, $target, $weight));
*  var_dump($ems->calculate($from, $country, $weight, \shipping\Ems::TYPE_ATT));
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
    * @return array
    */
    public function getRegions()
    {
        return $this->_filterRawLocations('regions');
    }

    /**
    * Returns filtered set of cities
    *
    * @return array
    */
    public function getCities()
    {
        return $this->_filterRawLocations('cities');
    }

    /**
    * Returns filtered set of countries
    *
    * @return array
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
    * @param  float  $weight in KG
    * @param  string $type (Default: null)
    * @return stdClass
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

        // response
        return $this->_request($client)->rsp;
    }

    /**
    * Returns set of objects with the region data.
    * Available: "cities", "regions", "countries" or "russia"
    *
    * @param  string $type (Default: russia)
    * @return array
    */
    public function getRawLocations($type = 'russia')
    {
        $client = $this->_getHttpClient()
            ->setParameterGet('method', 'ems.get.locations')
            ->setParameterGet('type', $type);

        return $this->_request($client)->rsp->locations;
    }

    /**
    * Returns the max weight of a package
    *
    * @return float
    */
    public function getMaxWeight()
    {
        $client = $this->_getHttpClient()
            ->setParameterGet('method', 'ems.get.max.weight');

        return $this->_request($client)->rsp->max_weight;
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
    * @return array
    */
    protected function _filterRawLocations($type)
    {
        $set = array();
        foreach ($this->getRawLocations($type) as $location) {
            if ($location->type == $type) {
                $set[$location->name] = $location->value;
            }
        }
        return $set;
    }
}
