<?php

// Check if the file path is provided
if ($argc < 2) 
{
    echo "Usage: php script.php <path_to_csv_file>\n";
    exit(1);
}

$filePath = $argv[1]; // Get the CSV file path from command line arguments

// Open the CSV file for reading
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
            "buy" => isset($row[3]) ? (float)$row[5] : null,
        ];

        /*if (!empty($row[2]) && !empty($row[3])) 
        {
            $jsonData["buy_currency"] = $row[2];
            $jsonData["buy"] = (float)$row[3];
        }

        if (!empty($row[4]) && !empty($row[5])) 
        {
            $jsonData["sell_currency"] = $row[4];
            $jsonData["sell"] = (float)$row[5];
        }*/

        $csvData[] = $jsonData;
    }
    fclose($handle);
}

echo json_encode($csvData, JSON_PRETTY_PRINT);
?>
