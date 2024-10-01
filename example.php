<?php

require_once('src/BMKG/BmkgNext.php');

use BmkgSdk\BMKGNext;

$bmkg = new BMKGNext();
$bmkg->setParams(["adm1"=>"21"]);
$result = $bmkg->getForecast();
header('Content-Type: application/json');
echo json_encode($result);