<?php
/* error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', 0); */

require_once __DIR__ . '/../vendor/autoload.php';
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverPoint;
define('CACHE_FILE', 'cache/data_cache.json');
define('CACHE_TTL', 3600);

function getCacheData() {
    if (file_exists(CACHE_FILE)) {
        $cacheContent = file_get_contents(CACHE_FILE);
        $cacheData = json_decode($cacheContent, true);

        // Check if the cache has a valid timestamp
        if (isset($cacheData['timestamp']) && (time() - $cacheData['timestamp'] < CACHE_TTL)) {
            return $cacheData['data']; // Return cached data
        }
    }
    return null;
}
function saveCacheData($data) {
    $cacheData = [
        'data' => $data,
        'timestamp' => time()
    ];
    file_put_contents(CACHE_FILE, json_encode($cacheData));
}

$cacheData = getCacheData();
if ($cacheData !== null) {
    $response = $cacheData;
} else {
    $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.82 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.1 Safari/605.1.15',
    ];
    $randomAgent = $userAgents[array_rand($userAgents)];
    $capabilities = DesiredCapabilities::chrome();
    $capabilities->setCapability('chromeOptions', ['args' => ["--user-agent=$randomAgent"]]);
    $host = 'http://localhost:20653';

    $driver = RemoteWebDriver::create($host, $capabilities);

    $driver->manage()->window()->setPosition(new WebDriverPoint(100, 100));
    $driver->manage()->window()->setSize(new WebDriverDimension(1200, 800));

        $driver->get('https://onoff.ee/et/62-nutitelefonid');
        $responseData = [];
        $h3Elements = $driver->findElements(WebDriverBy::tagName('h3'));
        $h3texts = [];
        foreach ($h3Elements as $h3Element) {
            $h3texts[] = $h3Element->getText();
        }

        $spanElements = $driver->findElements(WebDriverBy::className('price'));
        $prices = [];
        foreach ($spanElements as $spanElement) {
            $prices[] = $spanElement->getText();
        }

        $combineData = [];
        for ($i = 0; $i < count($prices); $i++) {
            if (isset($h3texts[$i]) && isset($prices[$i])) {
                $combineData[] = [
                    'name' => $h3texts[$i],
                    'price' => $prices[$i],
                ];
            }
        }

        // load more
        $btn = $driver->findElement(WebDriverBy::cssSelector('.infinite-more-link.btn.btn-default.btn-large'));
        if ($btn->isDisplayed()) {
            $btn->click();
            file_put_contents('log.txt', 'btn clicked!' . PHP_EOL, FILE_APPEND);

            try {
                // wait for next content
                $driver->wait(60)->until(
                    WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('h3'))
                );

                // Ccapture new one
                $newH3Elements = $driver->findElements(WebDriverBy::tagName('h3'));
                $newH3Texts = [];
                foreach ($newH3Elements as $h3Element) {
                    $newH3Texts[] = $h3Element->getText();
                }

                $newPriceElements = $driver->findElements(WebDriverBy::className('price'));
                $newPrices = [];
                foreach ($newPriceElements as $priceElement) {
                    $newPrices[] = $priceElement->getText();
                }

                // Combine
                for ($i = 0; $i < count($newPrices); $i++) {
                    if (isset($newH3Texts[$i]) && isset($newPrices[$i])) {
                        $combineData[] = [
                            'name' => $newH3Texts[$i],
                            'price' => $newPrices[$i],
                        ];
                    }
                }
            } catch (TimeoutException $e) {
                file_put_contents('log.txt', 'TimeoutException: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            }
        } else {
            echo "Element not visible";
        }
    $driver->quit();
    saveCacheData($combineData);
    $response = $combineData;
}
header('Content-Type: application/json');
echo json_encode(['response' => $response]);






