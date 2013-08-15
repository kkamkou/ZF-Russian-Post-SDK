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

namespace Shipping;

/**
* Api for the SPSR post service, that uses Zend Framework
* @see ApiAbstract
*/
class Spsr extends ApiAbstract
{
    // uris for the SPSR service
    const URI_CALC    = 'http://www.cpcr.ru/cgi-bin/postxml.pl?TariffCompute_2&254';
    const URI_CITY    = 'http://www.cpcr.ru/cgi-bin/postxml.pl?GetCityName&15';
    const URI_REGIONS = 'http://www.cpcr.ru/cgi-bin/postxml.pl?Regions';
    const URI_SID     = 'http://www.cpcr.ru/cgi-bin/postxml.pl?GetSID&37';

    /**
    * Finds City by the title provided
    *
    * @param  string $title
    * @return false|SimpleXMLElement
    */
    public function findCity($title)
    {
        // default set
        $set = array();

        // the client object
        $client = $this->_getHttpClient()
            ->setUri(self::URI_CITY)
            ->setParameterGet('CityName', $this->_encode($title));

        // the xml object
        $xml = $this->_request($client);

        // have we error?
        if ($this->hasError()) {
            return false;
        }

        // normalization
        foreach ($xml->City as $city) {
            $set[] = (array)$city;
        }

        return $set;
    }

    /**
    * Returns SID according the credentials
    *
    * @param  string $login
    * @param  string $password
    * @return string|false
    */
    public function getSid($login, $password)
    {
        // the client object
        $client = $this->_getHttpClient()
            ->setUri(self::URI_SID)
            ->setParameterGet('ContrNum', $this->_encode($login))
            ->setParameterGet('passw', $this->_encode($password));

        // the xml object
        $xml = $this->_request($client);

        // have we error?
        if ($this->hasError()) {
            return false;
        }

        // the current sid
        return (string)$xml->Session->sid;
    }

    /**
    * Returns hash with regions
    *
    * @return array
    */
    public function getRegions()
    {
        return $this->_getRegionsHash('Regions');
    }

    /**
    * Returns hash with countries
    *
    * @return array
    */
    public function getCountries()
    {
        return $this->_getRegionsHash('Countries');
    }

    /**
    * Makes calculation according options
    *
    * @param  array $options
    * @throws \UnexpectedValueException if a required option skipped
    * @return array|false
    */
    public function calculate(array $options)
    {
        // required fileds
        $required = array('ToCity', 'FromCity', 'Weight');
        foreach ($required as $key) {
            if (empty($options[$key])) {
                throw new \UnexpectedValueException(
                    "The '{$key}' option is required"
                );
            }
        }

        // the weight normalization (in kg)
        $options['Weight'] = sprintf('%0.2f', $options['Weight'] / 1000);

        // optional fields
        $optional = array(
            'Amount' => 0,
            'AmountCheck' => 1,
            'SMS' => 0,
            'BeforeSignal' => 0,
            'PlatType' => 1,
            'DuesOrder' => 0,
            'SID' => 'undefined'
        );

        // let's append them
        foreach ($optional as $key => $value) {
            if (!array_key_exists($key, $options)) {
                $options[$key] = $value;
            }
        }

        // let's apply properties
        $client = $this->_getHttpClient()->setUri(self::URI_CALC);
        foreach ($options as $key => $value) {
            if (!is_numeric($value)) {
                $value = $this->_encode($value);
            }

            $client->setParameterGet($key, $value);
        }

        // the xml object
        $xml = $this->_request($client);

        // results
        if ($this->hasError()) {
            return false;
        }

        // if we need only one tariff
        if (isset($options['usldost'])) {
            $xpathResult = $xml->xpath('/root/Tariff[UslDost="' . $options['usldost'] . '"]');
            if ($xpathResult !== false) {
                $results = (array)$xpathResult[0];
            }
        }

        // if we have no result so far, lets provide first of them
        if (!isset($results)) {
            $results = (array)$xml->Tariff;
        }

        // it's SPSR dude :)
        if (!isset($results['Total_Dost'])) {
            $this->setError('Incorrect format of the answer');
            return false;
        }

        $results['Total_Dost'] = strtr($results['Total_Dost'], ',', '.');
        return $results;
    }

    /**
    * Converts charset of the given string
    *
    * @param  string $string
    * @return string
    */
    protected function _encode($string)
    {
        if (extension_loaded('mbstring')) {
            return mb_convert_encoding($string, 'cp1251', 'utf-8');
        }
        return iconv('utf-8', 'cp1251', $string);
    }

    /**
    * Returns filtered set of regions
    *
    * @param  string $type
    * @return array|false
    */
    protected function _getRegionsHash($type)
    {
        // the xml object
        $xml = $this->_request(
            $this->_getHttpClient()->setUri(self::URI_REGIONS)
        );

        // have we error?
        if ($this->hasError()) {
            return false;
        }

        // the name of the keys, feel the difference!
        $keyName = ($type == 'Countries') ? 'Country_Name' : 'RegionName';

        // let's create hash
        $set = array();
        foreach ($xml->{$type} as $entry) {
            $set[trim($entry->attributes()->{$keyName})] = array(
                (int)$entry->attributes()->Id,
                (int)$entry->attributes()->Owner_Id
            );
        }

        // cleanup
        unset($set['']); // russian developers can create everything!

        // normalized hash
        return $set;
    }

    /**
    * Returns current HTTP client
    *
    * @return \Zend_Http_Client
    */
    protected function _getHttpClient()
    {
        return parent::getHttpClient()->resetParameters(true);
    }

    /**
    * Makes request and checks the result
    *
    * @param  \Zend_Http_Client $client
    * @throws \Exception if XML parser returned the error
    * @return \SimpleXMLElement
    */
    protected function _request(\Zend_Http_Client $client)
    {
        // the response body
        $body = parent::_request($client)->getBody();

        // the response has XML format, we should parse it
        $oldState = libxml_use_internal_errors(true);

        // errors cleanup
        libxml_clear_errors();

        // the xml object
        $xml = simplexml_load_string($body);

        // restoring default state
        libxml_use_internal_errors($oldState);

        // oops
        if (libxml_get_last_error()) {
            throw new \Exception(
                'XML parser returned the error: ' . libxml_get_last_error()->message
            );
        }

        // response has error
        if (isset($xml->Error)) {
            $this->setError(
                $xml->Error->attributes()->Type . ': ' .
                $xml->Error->attributes()->SubType
            );
        }

        return $xml;
    }
}
