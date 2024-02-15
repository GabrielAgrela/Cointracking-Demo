<?php

class CoinTrackingDemo
{
    
    public function run($filePath)
    {
        echo "File path: " . $filePath . "\n";
        if (!file_exists($filePath) || !is_readable($filePath)) 
        {
            echo "Error: File not found or not readable\n";
            exit(1);
        }

        $csvFile = fopen('Data/sample.csv', 'r');
        $csvData = [];

        while (($row = fgetcsv($csvFile, 1000, ",")) !== FALSE) 
        {
            $csvData[] = $row;
        }

        fclose($csvFile);

        // while csvfile count > 1
        while (count($csvData) > 1)
        {
            echo "*-----------------------------*\n";
            $smallestSellChangeRow = $this->getSmallestChange($csvData);
            echo "Row with smallest change: " . json_encode($smallestSellChangeRow, JSON_PRETTY_PRINT) . "\n";
            $smallestSellChangeRowPair = $this->getSmallestChange($csvData,$smallestSellChangeRow);
            echo "222Row with smallest change: " . json_encode($smallestSellChangeRowPair, JSON_PRETTY_PRINT) . "\n";
            $smallestFeeChangeRow = $this->getSmallestChange($csvData, null, "Fee");
            echo "333Row with smallest fee change: " . json_encode($smallestFeeChangeRow, JSON_PRETTY_PRINT) . "\n";
            $smallestReferralChangeRow = null;

            if ($smallestSellChangeRow === null || $smallestSellChangeRowPair === null || $smallestFeeChangeRow === null) 
            {
                $smallestReferralChangeRow = $this->getSmallestChange($csvData, null, "Referral Kickback");
                echo "444Row with smallest fee change: " . json_encode($smallestReferralChangeRow, JSON_PRETTY_PRINT) . "\n";
            }
            // Remove rows from csvData
            foreach ($csvData as $key => $row) 
            {
                if ($row === $smallestSellChangeRow || $row === $smallestSellChangeRowPair || $row === $smallestFeeChangeRow || $row === $smallestReferralChangeRow) 
                {
                    unset($csvData[$key]);
                }
            }
            
            //echo "CSV Data: " . json_encode($csvData, JSON_PRETTY_PRINT) . "\n";
            
        }
    }

    public function getSmallestChange($csvData, $smallestSellChangeRow = null, $type = "Sell")
    {
        $smallestChange = PHP_INT_MAX;
        $smallestSellChangeRowResult = null;
        foreach ($csvData as $row) 
        {
            if (isset($row[3]) && $row[3] == $type) 
            {
                if ($smallestSellChangeRow !== null && $row[4] == $smallestSellChangeRow[4]) 
                {
                    continue;
                }
                $change = abs(isset($row[5]) ? (float)$row[5] : null);
                if ($change < $smallestChange) 
                {
                    $smallestChange = $change;
                    $smallestSellChangeRowResult = $row;
                }
            }
        }
        return $smallestSellChangeRowResult;
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