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

        $csvData = [];
        if (($handle = fopen($filePath, 'r')) !== false) 
        {
            fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) 
            {
                $jsonData = 
                [
                    "time" => isset($row[0]) ? strtotime($row[1]) : null,
                    "type" => isset($row[3]) ? $row[3] : null,
                    "buy_currency" => isset($row[2]) ? $row[4] : null,
                    "buy" => isset($row[5]) ? (float)$row[5] : null,
                ];

                $csvData[] = $jsonData;
            }
            fclose($handle);
        }

        echo json_encode($csvData, JSON_PRETTY_PRINT);
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