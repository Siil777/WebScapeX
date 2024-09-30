<?php
require_once __DIR__ . '/vendor/autoload.php';
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;


$host = 'http://localhost:20653';

$driver = RemoteWebDriver::create($host, DesiredCapabilities::chrome());

$driver->get('https://onoff.ee/et/62-nutitelefonid');

$responseData = [];

$h3Elements = $driver->findElements(WebDriverBy::tagName('h3'));

$h3texts = [];
foreach($h3Elements as $h3Element){
    $h3texts[] = $h3Element->getText();
}


/* echo 'Page title is:' . $driver->getTitle(); */

$driver->quit();

header('Content-Type: application/json');
echo json_encode(['response'=> $h3texts]);