<?php

require_once('src/BMKG/Bmkg.php');

use BmkgSdk\BMKG;

$bmkg = new BMKG();
$bmkg->setDataPath("DigitalForecast-KepulauanRiau.xml");
$result = $bmkg->getForecast();
print_r($result);