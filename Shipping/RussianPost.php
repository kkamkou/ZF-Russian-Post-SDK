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
* Api for the Russian Post service, that uses Zend Framework
*/
class RussianPost extends \shipping\ShippingAbstract
{
    const URI_RESULTS = 'http://www.russianpost.ru/autotarif/Autotarif.aspx';

    /**
    * Stores predefined package types
    * @var array
    */
    protected $_typesPackage = array(
        'international' => array(
            23 => 'Заказная бандероль',
            12 => 'Заказная карточка',
            13 => 'Заказное письмо',
            55 => 'Заказное уведомление',
            30 => 'Заказной мелкий пакет',
            41 => "Заказной мешок 'М'",
            33 => 'Обыкновенная посылка',
            54 => 'СЕКОГРАММА',
            36 => 'Ценная посылка',
            16 => 'Ценное письмо'
        ),
        'local' => array(
            44 => 'EMS обыкновенный',
            45 => 'EMS с объявленной ценностью',
            18 => 'Европисьмо',
            23 => 'Заказная бандероль',
            52 => 'Заказная бандероль 1 кл.',
            12 => 'Заказная карточка',
            13 => 'Заказное письмо',
            50 => 'Заказное письмо 1 кл.',
            55 => 'Заказное уведомление',
            30 => 'Заказной мелкий пакет',
            41 => "Заказной мешок 'М'",
            54 => 'СЕКОГРАММА',
            26 => 'Ценная бандероль',
            53 => 'Ценная бандероль 1 кл.',
            36 => 'Ценная посылка',
            16 => 'Ценное письмо',
            51 => 'Ценное письмо 1 кл.'
        )
    );

    /**
    * Stores predefined delivery types
    * @var array
    */
    protected $_typesDelivery = array(
        'international' => array(
            1 => 'НАЗЕМН.',
            2 => 'АВИА'
        ),
        'local' => array(
            1 => 'НАЗЕМН.',
            2 => 'АВИА',
            3 => 'КОМБИН.',
            4 => 'УСКОР',
        )
    );

    /**
    * Returns set of package types
    *
    * @param  bool  $international (Default: false)
    * @return array
    */
    public function getTypesPackage($international = false)
    {
        return $international ? $this->_typesPackage['international']
            : $this->_typesPackage['local'];
    }

    /**
    * Returns set of delivery types
    *
    * @param  bool  $international (Default: false)
    * @return array
    */
    public function getTypesDelivery($international = false)
    {
        return $international ? $this->_typesDelivery['international']
            : $this->_typesDelivery['local'];
    }

    /**
    * Calculates international package price
    *
    * @link Country codes http://tinyurl.com/37syoog
    *
    * @param  string $pkgType
    * @param  int    $cryCode
    * @param  int    $dryType
    * @param  int    $weight (Default: 0) In grams
    * @param  int    $value (Default: 0)
    * @return string
    */
    public function calculateInternational($pkgType, $cryCode, $dryType, $weight = 0, $value = 0)
    {
        // the client object
        $client = $this->getHttpClient();

        // defaults
        $client->setParameterGet('viewPost', $pkgType)
            ->setParameterGet('countryCode', $cryCode)
            ->setParameterGet('typePost', $dryType)
            ->setParameterGet('weight', $weight)
            ->setParameterGet('value1', $value);

        // results alocation
        $result = $this->_request($client);

        // results validation
        return $this->hasError() ? false : $result;
    }

    /**
    * Calculates local package price
    *
    * @link http://ruspostindex.ru/
    *
    * @param  string $pkgType
    * @param  int    $index
    * @param  int    $dryType
    * @param  int    $weight (Default: 0) In grams
    * @param  int    $value (Default: 0)
    * @return string
    */
    public function calculateLocal($pkgType, $dryType, $index, $weight = 0, $value = 0)
    {
        // the client object
        $client = $this->getHttpClient();

        // defaults
        $client->setParameterGet('viewPost', $pkgType)
            ->setParameterGet('countryCode', 643) // russia
            ->setParameterGet('typePost', $dryType)
            ->setParameterGet('postOfficeId', $index)
            ->setParameterGet('weight', $weight)
            ->setParameterGet('value1', $value);

        // results alocation
        $result = $this->_request($client);

        // results validation
        return $this->hasError() ? false : $result;
    }

    /**
    * Makes request and checks the result
    *
    * @param  \Zend_Http_Client $client
    * @throws \Exception when the result was unsuccessful
    * @return string
    */
    protected function _request(\Zend_Http_Client $client)
    {
        // client adjustment
        $client->getAdapter()->setConfig(array('timeout' => 30));

        // default uri
        $client->setUri(self::URI_RESULTS);

        // the response body
        $body = parent::_request($client)->getBody();

        // answer has invalid structure, so, let's fetch only the body part
        $matches = array();
        if (!preg_match('~<body[^>]+>(?:.+?)</body>~s', $body, $matches)) {
            $this->setError('The body part not found in the response');
            return 0; // default value
        }

        // the DOM parser
        $dom = new \Zend_Dom_Query(current($matches), 'cp1251');

        // dom results
        $result = $dom->query('#TarifValue');

        // result validation
        if (!$result->count()) {
            $this->setError('The specified element is not found in the DOM document');
            return 0; // default value
        }

        // results
        return strtr($result->current()->nodeValue, ',', '.');
    }
}
