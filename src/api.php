<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', 0);

require_once __DIR__ . '/../vendor/autoload.php';
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverPoint;

define('CACHE_FILE', 'cache/data_cache.json');


function getCacheData() {
    if (file_exists(CACHE_FILE)) {
        $cacheContent = file_get_contents(CACHE_FILE);
        $cacheData = json_decode($cacheContent, true);
        if (isset($cacheData['data'])) {
            return $cacheData['data'];
        }
    }
    return null;
}

function saveCacheData($data) {
    file_put_contents(CACHE_FILE, json_encode(['data' => $data]));
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

    //position and size of the window
    $driver->manage()->window()->setPosition(new WebDriverPoint(100, 100));
    $driver->manage()->window()->setSize(new WebDriverDimension(1200, 800));

    try {
        $driver->get('https://onoff.ee/et/62-nutitelefonid');
        $responseData = [];

        // capture first block
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

        // click btn lae rohkem tooteid
        try {
            $btn = $driver->findElement(WebDriverBy::cssSelector('.infinite-more-link.btn.btn-default.btn-large'));
            if ($btn->isDisplayed()) {
                $btn->click();
                file_put_contents('log.txt', 'btn clicked!' . PHP_EOL, FILE_APPEND);

                // Wait for h3 and price
                $driver->wait(60)->until(
                    WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::tagName('h3'))
                );

                // Capture newly loaded data
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

                // Combine old and new data
                for ($i = 0; $i < count($newPrices); $i++) {
                    if (isset($newH3Texts[$i]) && isset($newPrices[$i])) {
                        $combineData[] = [
                            'name' => $newH3Texts[$i],
                            'price' => $newPrices[$i],
                        ];
                    }
                }

                file_put_contents('log.txt', 'New data captured: ' . print_r($combineData, true) . PHP_EOL, FILE_APPEND);
            } else {
                file_put_contents('log.txt', 'Load more button not visible' . PHP_EOL, FILE_APPEND);
            }
        } catch (NoSuchElementException $e) {
            file_put_contents('log.txt', 'Load more button not found: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    } catch (Exception $e) {
        file_put_contents('log.txt', 'Exception: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    } finally {
        $driver->quit();
    }
    saveCacheData($combineData);
    $response = $combineData;
    header('Content-Type: application/json');
    echo json_encode(['response' => $response]);
}
