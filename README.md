# bmkg-php
PHP wrapper for BMKG API

## Installation
This project using composer.
```
$ composer require kiiskominfokepri/bmkg-php
```

## Usage
Get forecasting data from BKMG API.
```php
<?php

use BmkgSdk\BMKG;

$bmkg = new BMKG();
$bmkg->setDataPath("DigitalForecast-KepulauanRiau.xml");
$result = $bmkg->getForecast();
echo json_encode($result);
```