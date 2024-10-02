<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;

// Define cache file path and TTL (Time-to-Live)
define('CACHE_FILE', 'cache/data_cache.json');
define('CACHE_TTL', 3600);

// Function to get cached data
function getCacheData() {
    if (file_exists(CACHE_FILE)) {
        $cacheContent = file_get_contents(CACHE_FILE);
        $cacheData = json_decode($cacheContent, true);

        // Check if cache is still valid
        if (isset($cacheData['timestamp']) && (time() - $cacheData['timestamp'] < CACHE_TTL)) {
            return $cacheData['data'];
        }
    }
    return null; // Return null if no valid cache exists
}

// Function to save data to cache
function saveCacheData($data) {
    $cacheData = [
        'timestamp' => time(),
        'data' => $data
    ];
    file_put_contents(CACHE_FILE, json_encode($cacheData));
}

// Check if valid cache exists
$cacheData = getCacheData();

if ($cacheData !== null) {
    // Use cached data
    $response = $cacheData;
} else {
    // Fetch new data if no valid cache is available
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

    // Start WebDriver
    $driver = RemoteWebDriver::create($host, $capabilities);

    // Open the target website
    $driver->get('https://onoff.ee/et/62-nutitelefonid');

    // Extract data from the page
    $h3Elements = $driver->findElements(WebDriverBy::tagName('h3'));
    $spanElements = $driver->findElements(WebDriverBy::cssSelector('.price.st_discounted_price'));

    // Collect phone names
    $h3texts = [];
    foreach ($h3Elements as $h3Element) {
        $h3texts[] = $h3Element->getText();
    }

    // Collect prices
    $prices = [];
    foreach ($spanElements as $spanElement) {
        $prices[] = $spanElement->getText();
    }

    // Combine phone names with prices
    $combineData = [];
    for ($i = 0; $i < count($prices); $i++) {
        if (isset($h3texts[$i]) && isset($prices[$i])) {
            $combineData[] = [
                'name' => $h3texts[$i],
                'price' => $prices[$i],
            ];
        }
    }

    // Close WebDriver
    $driver->quit();

    // Save data to cache
    saveCacheData($combineData);

    // Use fresh data
    $response = $combineData;
}

// Return response as JSON
header('Content-Type: application/json');
echo json_encode(['response' => $response]);