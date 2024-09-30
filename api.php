<?php
require_once __DIR__ . '/vendor/autoload.php';
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;

$userAgents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.82 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.1 Safari/605.1.15',
];

// Randomly select a User-Agent
$randomAgent = $userAgents[array_rand($userAgents)];

// Set desired capabilities including the User-Agent
$capabilities = DesiredCapabilities::chrome();
$capabilities->setCapability('chromeOptions', ['args' => ["--user-agent=$randomAgent"]]);
$host = 'http://localhost:20653';

$driver = RemoteWebDriver::create($host, $capabilities);

$driver->get('https://onoff.ee/et/62-nutitelefonid');

$responseData = [];

$h3Elements = $driver->findElements(WebDriverBy::tagName('h3'));

$h3texts = [];
foreach($h3Elements as $h3Element){
    $h3texts[] = $h3Element->getText();
}
$spanElements = $driver->findElements(WebDriverBy::cssSelector('.price.st_discounted_price'));
$prices = [];
foreach ($spanElements as $spanElement) {
    $prices[] = $spanElement->getText();
}
$combineData = [];
for ($i =0 ; $i < count($prices); $i ++ ){
    if(isset($h3texts[$i]) && isset($prices[$i])){
        $combineData[] = [
            'name'=> $h3texts[$i],
            'price'=> $prices[$i],
        ];
    }
}


/* echo 'Page title is:' . $driver->getTitle(); */

$driver->quit();

header('Content-Type: application/json');
echo json_encode(['response'=> $combineData]);