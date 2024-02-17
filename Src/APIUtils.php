<?php
require 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

class APIUtils
{
    private static $apiKey = null;
    private static $coinMap = [];

    // get the (and add the api key url parameter) API key from the environment variables
    public static function getApiKey()
    {
        if (self::$apiKey === null) 
        {
            if ($_ENV["API_KEY"] !== null && $_ENV["API_KEY"] !== "")
                self::$apiKey = "&x_cg_demo_api_key=".$_ENV["API_KEY"];
        }
        return self::$apiKey;
    }

    // fetch the price of a coin in euro at a specific date
    public static function fetchCoinPriceInEuro($coin, $date)
    {
        // first fetch the coin id from the coin name
        $coin = APIUtils::fetchCoinIDCurl($coin);
        if (is_int($coin) || $coin === "Unknown error, coin ID not found.")
        {
            return "Error fetching coin id - " . $coin;
        }
        // translate the date to the correct format
        $date = APIUtils::translateDate($date);

        // fetch the coin price in euro
        return APIUtils::fetchCoinPriceInEuroCurl($coin, $date);

    }

    public static function fetchCoinIDCurl($coin)
    {
        if (isset(APIUtils::$coinMap[$coin]))
            return APIUtils::$coinMap[$coin];

        $url = "https://api.coingecko.com/api/v3/search?query=$coin" . APIUtils::getApiKey();
        $data = APIUtils::makeCurlRequest($url);

        if (isset($data['coins']) && count($data['coins']) > 0) {
            APIUtils::$coinMap[$coin] = $data['coins'][0]['id'];
            return $data['coins'][0]['id'];
        } else {
            return APIUtils::handleError($data, $coin, 'fetchCoinIDCurl');
        }
    }

    public static function fetchCoinPriceInEuroCurl($coin, $date)
    {
        $url = "https://api.coingecko.com/api/v3/coins/$coin/history?date=$date&localization=false" . APIUtils::getApiKey();
        $data = APIUtils::makeCurlRequest($url);

        if (isset($data['market_data']['current_price']['eur'])) {
            return $data['market_data']['current_price']['eur'];
        } else {
            return APIUtils::handleError($data, 'fetchCoinPriceInEuroCurl', $coin, $date);
        }
    }

    public static function makeCurlRequest($url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }

    public static function handleError($data, $retryFunction, ...$args)
    {
        if (isset($data['status']['error_code'])) 
        {
            if ($data['status']['error_code'] === 429) 
            {
                echo "\nAPI limit reached, Retrying $retryFunction in 10s... \n";
                sleep(10);
                return call_user_func_array(array('APIUtils', $retryFunction), $args);
            } 
            else 
            {
                return "Error fetching data - " . $data['status']['error_code'];
            }
        } 
        else 
        {
            return "Unknown error, data not found.";
        }
    }

    // translate the date to unix time
    public static function translateDate($date)
    {
        return date('d-m-Y', strtotime($date));
    }
    

    

    
}
