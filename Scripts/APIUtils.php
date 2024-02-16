<?php

class APIUtils
{
    private static $apiKey = "CG-phrpEknLmGCjgVpcerKyPs7e";
    public static function fetchCoinPriceInEuro($coin, $date)
    {
        $coin = APIUtils::translateCoin($coin);
        $date = APIUtils::translateDate($date);
        
        $url = "https://api.coingecko.com/api/v3/coins/$coin/history?date=$date&localization=false&x_cg_demo_api_key=".APIUtils::$apiKey;
        //echo "\n".$url;
        // Initialize a cURL session
        $curl = curl_init($url);

        // Set cURL options
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);

        // Execute the cURL session
        $response = curl_exec($curl);

        // Close the cURL session
        curl_close($curl);

        // Decode the JSON response
        $data = json_decode($response, true);

        // Check if the 'market_data' and 'current_price' fields are available
        if (isset($data['market_data']['current_price']['eur'])) 
        {
            return $data['market_data']['current_price']['eur']; 
        } 
        else if (isset($data['status']['error_code']))
        {
            return $data['status']['error_code'];
        }
        else 
        {
            return "Unknown error, market_data or current_price not found at $coin at $date.";
        }
    }

    public static function translateCoin($coin)
    {
        // Define a map of known coins and their ids
        $coinMap = [
            'bitcoin' => 'bitcoin',
            'ethereum' => 'ethereum',
            // Add more known coins here...
        ];

        // If the coin is in the map, return its id
        if (isset($coinMap[$coin])) 
        {
            return $coinMap[$coin];
        }

        // If the coin is not in the map, query the CoinGecko API to get the coin id
        $url = "https://api.coingecko.com/api/v3/search?query=$coin&x_cg_demo_api_key=".APIUtils::$apiKey;

        // Initialize a cURL session
        $curl = curl_init($url);

        // Set cURL options
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);

        // Execute the cURL session
        $response = curl_exec($curl);

        // Close the cURL session
        curl_close($curl);

        // Decode the JSON response
        $data = json_decode($response, true);

        // If the 'coins' field is available and it has at least one item, return the id of the first item
        if (isset($data['coins']) && count($data['coins']) > 0) 
        {
            return $data['coins'][0]['id'];
        }
        else if (isset($data['status']['error_code']))
        {
            return $data['status']['error_code'];
        }
        else 
        {
            return "Unknown error, coins not found.";
        }
        // If the coin id could not be found, return null
        return null;
    }

    public static function translateDate($date)
    {
        return date('d-m-Y', strtotime($date));
    }
}
