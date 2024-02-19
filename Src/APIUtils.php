<?php
require 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

class APIUtils
{
    private static $apiKey = null;
    private static $coinMap = [];
    private static $priceMap = [];

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
        $today = APIUtils::dateToUnixDay(date('d-m-Y') . ' 00:00:00');
        // fetch the coin price in euro
        return APIUtils::fetchCoinPriceInEuroCurl($coin, $date, $today);

    }

    // fetch the coin id from the coin name
    public static function fetchCoinIDCurl($coin)
    {
        if (isset(APIUtils::$coinMap[$coin]))
            return APIUtils::$coinMap[$coin];

        $url = "https://api.coingecko.com/api/v3/search?query=$coin" . APIUtils::getApiKey();
        $data = APIUtils::makeCurlRequest($url);

        if (isset($data['coins']) && count($data['coins']) > 0) 
        {
            APIUtils::$coinMap[$coin] = $data['coins'][0]['id'];
            return $data['coins'][0]['id'];
        } 
        else 
        {
            return APIUtils::handleError($data,  'fetchCoinIDCurl', $coin);
        }
    }

    // fetch the coin price in euro at a specific date 
    public static function fetchCoinPriceInEuroCurl($coin, $date, $today)
    {
        if (isset(APIUtils::$priceMap[$coin][$date]))
            return APIUtils::$priceMap[$coin][$date];
            
        $url = "https://api.coingecko.com/api/v3/coins/$coin/market_chart/range?vs_currency=eur&from=0&to=$today" . APIUtils::getApiKey();
        $data = APIUtils::makeCurlRequest($url);
        if (isset($data['prices'])) 
        {
            $priceAtDate = "Price not found at this date.";
            // save to map all the prices for the coin
            foreach ($data['prices'] as $price) 
            {
                APIUtils::$priceMap[$coin][$price[0]] = $price[1];
                if ($price[0] == $date)
                    $priceAtDate = $price[1];
            }
            return $priceAtDate;
        } 
        else 
        {
            return APIUtils::handleError($data, 'fetchCoinPriceInEuroCurl', $coin, $date, $today);
        }
    }

    public static function dateToUnixDay($date)
    {
        // returns the date in unix time but at the beggining of the day
        return strtotime(date('d-m-Y', strtotime($date)));
    }

    public static function fetchCoinPriceInEuroCache($coin, $date)
    {
        if (isset(APIUtils::$priceMap[$coin][$date]))
            return APIUtils::$priceMap[$coin][$date];
        else
            return APIUtils::fetchCoinPriceInEuro($coin, $date);
    }

    // make a curl request to the given url, used for all the API requests
    public static function makeCurlRequest($url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($curl);
        curl_close($curl);

        // sometimes, instead of a json response, the API returns a string with the error
        if (strpos($response, "Throttled") !== false)
            return ["status" => ["error_code" => 429]];
        else
            return json_decode($response, true);
    }

    // handle the error from the API request, if the error is a 429 (API limit reached) we retry the function after 10s
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
            // print full curl response for debugging
            
            return "Error unrelated with API cap, data not found.";

        }
    }

    // translate the date to unix time, considering the date is in UTC for this endpoint
    public static function translateDate($date)
    {
        $dt = new DateTime($date, new DateTimeZone('UTC'));
        $dt->setTime(0, 0, 0);
        return $dt->getTimestamp()*1000;
    }


    
}
