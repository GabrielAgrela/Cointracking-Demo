<?php
require_once 'FileUtils.php';
require_once 'APIUtils.php';
class CoinTrackingDemo
{
    // json var
    private $json = [];

    public function run($filePath)
    {
        FileUtils::checkFile($filePath);

        $data = FileUtils::readFile($filePath);

        // Remove header
        array_shift($data);

        // group by same utc_time
        $data = FileUtils::groupData($data);


        // sort chunk for each utc_time
        foreach ($data as $utcTime => $chunk) 
        {
            $this->processGroup($chunk);
        }
        echo json_encode($this->json, JSON_PRETTY_PRINT);
    }


    public function processGroup($csvData)
    {
        while (count($csvData) > 0)
        {
            $smallestTradeChangeRow = $this->getSmallestChange($csvData);
            if ($smallestTradeChangeRow === null) 
            {
                $smallestTradeChangeRow = $this->getSmallestChange($csvData, null, "Buy");
                $smallestTradeChangeRowPair = $this->getSmallestChange($csvData,$smallestTradeChangeRow, "Buy");
            }
            else
            {
                $smallestTradeChangeRowPair = $this->getSmallestChange($csvData, $smallestTradeChangeRow);
            }
            
            $smallestFeeChangeRow = $this->getSmallestChange($csvData, null, "Fee");
            $smallestReferralChangeRow = null;
            $smallestDepositChangeRow = null;
            $smallestMinningChangeRow = null;

            if ($smallestTradeChangeRow === null || $smallestTradeChangeRowPair === null || $smallestFeeChangeRow === null) 
            {
                $smallestReferralChangeRow = $this->getSmallestChange($csvData, null, "Referral Kickback");
                $this->addToJson($smallestReferralChangeRow, "Reward/Bonus");

                $smallestDepositChangeRow = $this->getSmallestChange($csvData, null, "Deposit");
                $this->addToJson($smallestDepositChangeRow, "Deposit");

                $smallestMinningChangeRow = $this->getSmallestChange($csvData, null, "Super BNB Mining");
                $this->addToJson($smallestMinningChangeRow, "Mining");
            }
            else
            {
                $this->addToJson($smallestTradeChangeRow, "Trade", $smallestTradeChangeRowPair);
                $this->addToJson($smallestFeeChangeRow, "Other fee");
            }

            // Remove rows from csvData
            foreach ($csvData as $key => $row) 
            {
                if ($row === $smallestTradeChangeRow || $row === $smallestTradeChangeRowPair || $row === $smallestFeeChangeRow || $row === $smallestReferralChangeRow || $row === $smallestDepositChangeRow || $row === $smallestMinningChangeRow) 
                {
                    unset($csvData[$key]);
                }
            }
        }
    }

    private function addToJson($row, $type, $pairRow = null)
    {
        if ($row !== null) 
        {
            $jsonEntry = [
                "time" => strtotime($row[1]),
                "type" => $type,
            ];

            $retryCount = 0;
            while ($retryCount < 30) 
            {
                if ($type === "Other fee") 
                {
                    $jsonEntry["sell_currency"] = $row[4];
                    $jsonEntry["sell"] = abs(floatval($row[5]));
                    $coinPrice = APIUtils::fetchCoinPriceInEuro($row[4],  $row[1]);
                    if (is_float($coinPrice)) {
                        $jsonEntry["sell_eur"] = $coinPrice * $jsonEntry["sell"];
                    } else {
                        $jsonEntry["sell_eur"] = $coinPrice;
                    }
                } 
                else 
                {
                    $jsonEntry["buy_currency"] = $row[4];
                    $jsonEntry["buy"] = abs(floatval($row[5]));
                    $coinPrice = APIUtils::fetchCoinPriceInEuro($row[4],  $row[1]);
                    if (is_float($coinPrice)) {
                        $jsonEntry["buy_eur"] = APIUtils::fetchCoinPriceInEuro($row[4],  $row[1]);
                    } else {
                        $jsonEntry["buy_eur"] = $coinPrice;
                    }
                }
                
                if ($pairRow !== null) 
                {
                    $jsonEntry["sell_currency"] = $pairRow[4];
                    $jsonEntry["sell"] = abs(floatval($pairRow[5]));
                    $coinPrice = APIUtils::fetchCoinPriceInEuro($pairRow[4],  $pairRow[1]);
                    if (is_float($coinPrice)) {
                        $jsonEntry["sell_eur"] = $coinPrice * $jsonEntry["sell"];
                    } else {
                        $jsonEntry["sell_eur"] = $coinPrice;
                    }
                }

                if ((isset($jsonEntry["sell_eur"]) && $jsonEntry["sell_eur"] === 429) || (isset($jsonEntry["buy_eur"]) && $jsonEntry["buy_eur"] === 429))
                {
                    echo "\n Retrying... \n";
                    sleep(5); // Wait for 1 second before retrying
                    $retryCount++;
                    continue;
                }

                break;
            }

            $this->json[] = $jsonEntry;
            //echo json_encode($jsonEntry, JSON_PRETTY_PRINT) . "\n";
        }
    }

    public function getSmallestChange($csvData, $smallestChangeRow = null, $type = "Sell")
    {
        $smallestChange = PHP_INT_MAX;
        $smallestChangeRowResult = null;
        foreach ($csvData as $row) 
        {
            if (isset($row[3]) && $row[3] == $type) 
            {
                if ($smallestChangeRow !== null && $row[4] == $smallestChangeRow[4]) 
                {
                    continue;
                }
                $change = abs(isset($row[5]) ? (float)$row[5] : null);
                if ($change < $smallestChange) 
                {
                    $smallestChange = $change;
                    $smallestChangeRowResult = $row;
                }
            }
        }
        if ($smallestChangeRowResult !== null) 
        {
           // echo "444Row with smallest fee change: " . json_encode($smallestChangeRowResult, JSON_PRETTY_PRINT) . "\n";
        }
        return $smallestChangeRowResult;
    }
}

if ($argc < 2) 
{
    echo "Usage: php script.php <path_to_csv_file>\n";
    exit(1);
}

$coinTrackingDemo = new CoinTrackingDemo();
$coinTrackingDemo->run($argv[1]);
?>