# Requirements
* PHP 5.3+
* iconv or mbsrting extensions for the SPSR
* Zend Framework
* Also you need to have nerves to work with the SPSR :)

## RussianPost Examples
### Package calculation:

```php
<?php
$post = new \Shipping\RussianPost();
$postcode = 454013;
$weight = 400; // 0.4kg
var_dump($post->calculateLocal(26, 1, $postcode, $weight));
```

### International package calculation:

```php
<?php
$post = new \Shipping\RussianPost();
$ccode = 112; // BY
$weight = 400; // 0.4kg
var_dump($post->calculateInternational(26, $ccode, $postcode, $weight));
```

### Misc information
```php
<?php
$post = new \Shipping\RussianPost();

// the package types
var_dump($post->getTypesPackage());
var_dump($post->getTypesPackage(true)); // international

// the delivery types
var_dump($post->getTypesDelivery());
var_dump($post->getTypesDelivery(true)); // international
```

## EMS Examples
### Package calculation:

```php
<?php
$ems = new \Shipping\Ems();

$from = 'city--moskva';
$target = 'city--astrahan';
$weight = floatval(1200 / 1000);
$country = 'US';

// default
var_dump($ems->calculate($from, $target, $weight));

// international
var_dump($ems->calculate($from, $country, $weight, \Shipping\Ems::TYPE_ATT));
```

### Regions, Cities and Countries:

```php
<?php
$ems = new \Shipping\Ems();

var_dump($ems->getRegions());
var_dump($ems->getCities());
var_dump($ems->getCountries());

// you can also get unformatted data using: "cities", "regions", "countries" or "russia"
var_dump($ems->getRawLocations('cities'));
```

### Max package weight:

```php
<?php
$ems = new \Shipping\Ems();

var_dump($ems->getMaxWeight());
```

## SPSR Examples

### Auth process (optional):

```php
<?php
$spsr = new \Shipping\Spsr();
$sid = $spsr->getSid(123321, 132234);
```

### Package calculation:
```php
<?php
$spsr = new \Shipping\Spsr();

$options = array(
  'Country' => '209|0',
  'ToRegion' => '7|0',
  'ToCity' => '124|0',
  'FromCountry' => '209|0',
  'FromRegion' => '40|0',
  'FromCity' => '992|0',
  'Weight' => 1000, // KG
  'usldost'  => 'st_5_0', // optional, standart one.
  'SID' => $spsr->getSid('login', 'password') // optional
);

var_dump($spsr->calculate($options));
```

### Regions and Countries

```php
<?php
$spsr = new \Shipping\Spsr();

// list of entries with first and second value for the calculation
var_dump($spsr->getRegions());
var_dump($spsr->getCountries());
```

### City or Cities:

```php
<?php
$spsr = new \Shipping\Spsr();

// one entry
$spsr->findCity('Москва');

// more than one entry
$spsr->findCity('Челя');
```

## Error handling
All functions return __false__ in case the post service provides incorrect answer.

## Exceptions
Exception - client request failed

UnexpectedValueException - service provides unexpected data
