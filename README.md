## Requirements
* PHP 5.3+
* iconv or mbsrting extensions for the SPSR
* Zend Framework

## EMS examples
Package calculation:
```php
    $ems = new \shipping\Ems();
    
    $from = 'city--moskva';
    $target = 'city--astrahan';
    $weight = floatval(1200 / 1000);
    $country = 'US';

    // default
    var_dump($ems->calculate($from, $target, $weight));
    
    // international
    var_dump($ems->calculate($from, $country, $weight, \shipping\Ems::TYPE_ATT));
```