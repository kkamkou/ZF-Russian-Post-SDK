# Requirements
* PHP 5.3+
* iconv or mbsrting extensions for the SPSR
* Zend Framework

## EMS Examples
### Package calculation:

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

### Regions, Cities and Countries:

```php
$ems = new \shipping\Ems();

var_dump($ems->getRegions());
var_dump($ems->getCities());
var_dump($ems->getCountries());

// you can also get unformatted data using: "cities", "regions", "countries" or "russia"
var_dump($ems->getRawLocations('cities'));
```

### Max package weight:

```php
$ems = new \shipping\Ems();

var_dump($ems->getMaxWeight());
```

## SPSR Examples

### Auth process (optional):

```php
$sid = $spsr->getSid(123321, 132234);
```

### Package calculation:
```php
$spsr = new \shipping\Spsr();

$options = array(
  'Country' => '209|0',
  'ToRegion' => '7|0',
  'ToCity' => '124|0',
  'FromCountry' => '209|0',
  'FromRegion' => '40|0',
  'FromCity' => '992|0',
  'Weight' => 1, // KG
  'SID' => $spsr->getSid('login', 'password') // optional
);

var_dump($spsr->calculate($options));
```

### Regions and Countries

```php
$spsr = new \shipping\Spsr();

// list of entries with first and second value for the calculation
var_dump($spsr->getRegions());
var_dump($spsr->getCountries());
```

### City or Cities:

```php
$spsr = new \shipping\Spsr();

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