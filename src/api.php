<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverPoint;

define('CACHE_FILE', 'cache/data_cache.json');
define('OUTPUT_DIR', __DIR__ . '/output');

function getCacheData(){
    if(file_exists(CACHE_FILE)){
        $cacheContent = file_get_contents(CACHE_FILE);
        $cacheData = json_decode($cacheContent, true);
        if(isset($cacheData['data'])){
            return $cacheData['data'];
        }
    }
    return null;
}
function saveCacheData($data){
    file_put_contents(CACHE_FILE, json_encode(['data'=>$data]));
}
$cacheData = getCacheData();
if($cacheData !== null){
    $response = $cacheData;
}else{
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

    try{


    // Set the position and size of the window
    $driver->manage()->window()->setPosition(new WebDriverPoint(100, 100));
    $driver->manage()->window()->setSize(new WebDriverDimension(1200, 800));

    $driver->get('https://onoff.ee/et/62-nutitelefonid');

    // Wait for the page to load completely
    $driver->wait(30)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.infinite-more-link.btn.btn-default.btn-large'))
    );

    // Scroll to the button to ensure it is in view
    $btn = $driver->findElement(WebDriverBy::cssSelector('.infinite-more-link.btn.btn-default.btn-large'));
    $driver->executeScript("arguments[0].scrollIntoView(true);", [$btn]);

    $responseData = [];

    $h3Elements = $driver->findElements(WebDriverBy::tagName('h3'));

    $h3texts = [];
    foreach($h3Elements as $h3Element){
        $h3texts[] = $h3Element->getText();
    }
    $spanElements = $driver->findElements(WebDriverBy::className('price'));
    $prices = [];
    foreach ($spanElements as $spanElement) {
        $prices[] = $spanElement->getText();
    }
    $combineData = [];
    for ($i = 0; $i < count($prices); $i++){
        if(isset($h3texts[$i]) && isset($prices[$i])){
            $combineData[] = [
                'name'=> $h3texts[$i],
                'price'=> $prices[$i],
            ];
        }
    }
    if($btn->isDisplayed()){
        $btn->click();
        file_put_contents('log.txt', 'btn clicked!' . PHP_EOL, FILE_APPEND);

        try {
            // Wait for the new content to load with increased timeout
            $driver->wait(60)->until(
                WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.new-content-selector'))
            );

            // Capture the new content
            $newContentElements = $driver->findElements(WebDriverBy::cssSelector('.new-content-selector'));
            $newContent = [];
            foreach ($newContentElements as $element) {
                $newContent[] = $element->getText();
            }

            // Combine new content with existing data
            $combineData = array_merge($combineData, $newContent);
        } catch (TimeoutException $e) {
            // Log the timeout exception
            file_put_contents('log.txt', 'TimeoutException: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }else{
        echo "element not visible";
    }
    }catch (Exception $e){
        echo "No such window";
    }

    // Ensure the output directory exists
    if (!file_exists(OUTPUT_DIR)) {
        mkdir(OUTPUT_DIR, 0777, true);
    }

    // Save screenshot in the output directory
    $driver->takeScreenshot(OUTPUT_DIR . '/screenshot_after_click.png');

    // Save HTML page source in the output directory
    file_put_contents(OUTPUT_DIR . '/page_source_after_click.html', $driver->getPageSource());

    $driver->quit();

    saveCacheData($combineData);
    $response = $combineData;

}
header('Content-Type: application/json');
echo json_encode(['response'=> $response]);





