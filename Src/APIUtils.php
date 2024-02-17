<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
class APIUtils
{
    private static $apiKey = null;
    private static $coinMap = [];

    public static function getApiKey()
    {
        if (self::$apiKey === null) 
        {
            if ($_ENV["API_KEY"] !== null && $_ENV["API_KEY"] !== "")
                self::$apiKey = "&x_cg_demo_api_key=".$_ENV["API_KEY"];
        }
        return self::$apiKey;
    }

    public static function fetchCoinPriceInEuro($coin, $date)
    {
       
        $coin = APIUtils::fetchCoinIDCurl($coin);
        if (is_int($coin) || $coin === "Unknown error, coin ID not found.")
        {
            return "Error fetching coin id - " . $coin;
        }
        $date = APIUtils::translateDate($date);
        return APIUtils::fetchCoinPriceInEuroCurl($coin, $date);
        
    }

    public static function fetchCoinPriceInEuroCurl($coin, $date)
    {
        $url = "https://api.coingecko.com/api/v3/coins/$coin/history?date=$date&localization=false". APIUtils::getApiKey();
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);

        $response = curl_exec($curl);

        curl_close($curl);

        $data = json_decode($response, true);

        if (isset($data['market_data']['current_price']['eur'])) 
        {
            return $data['market_data']['current_price']['eur'];
        } 
        else if (isset($data['status']['error_code'])) 
        {
            if ($data['status']['error_code'] === 429) 
            {
                echo "\n API limit reached, Retrying fetchCoinPriceInEuroCurl in 10s... \n";
                sleep(10);
                return APIUtils::fetchCoinPriceInEuroCurl($coin, $date);
            } 
            else 
            {
                return "Error fecthing market_data or current_price - " . $data['status']['error_code'];
            }
        } 
        else 
        {
            var_dump($data);
            return "Unknown error, market_data or current_price not found at $coin at $date.";
        }
    }

    public static function fetchCoinIDCurl($coin)
    {
        if (isset(APIUtils::$coinMap[$coin])) 
            return APIUtils::$coinMap[$coin];
        $url = "https://api.coingecko.com/api/v3/search?query=$coin".APIUtils::getApiKey();
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $response = curl_exec($curl);
        curl_close($curl);
        $data = json_decode($response, true);


        if (isset($data['coins']) && count($data['coins']) > 0) 
        {
            APIUtils::$coinMap[$coin] = $data['coins'][0]['id'];
            return $data['coins'][0]['id'];
        }
        else if (isset($data['status']['error_code']))
        {
            if ($data['status']['error_code'] === 429) 
            {
                echo "\n API limit reached, Retrying fetchCoinIDCurl in 10s... \n";
                sleep(10);
                return APIUtils::fetchCoinIDCurl($coin);
            }
            else 
            {
                return $data['status']['error_code'];
            }
        }
        else 
        {
            var_dump($data);
            return "Unknown error, coin ID not found.";
        }
    }

    public static function translateDate($date)
    {
        return date('d-m-Y', strtotime($date));
    }
}
