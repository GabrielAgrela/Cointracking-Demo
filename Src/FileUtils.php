<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require_once __DIR__ . '/../vendor/autoload.php';

class FileUtils
{
    // return group data by utc_time
    public static function groupData($sheetData)
    {
        $groupedData = [];
        foreach ($sheetData as $row) {
            $utcTime = $row[1];
            if (!isset($groupedData[$utcTime])) {
                $groupedData[$utcTime] = [];
            }
            $groupedData[$utcTime][] = $row;
        }
        return $groupedData;
    }

    // check if file exists and is readable
    public static function checkFile($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            echo "Error: File not found or not readable\n";
            exit(1);
        }
    }

    // read file and return data depending on the file type
    public static function readFile($filePath)
    {
        // Determine file type by extension
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Read CSV file
        if ($fileExtension == 'csv') 
        {
            $csvFile = fopen($filePath, 'r');
            $data = [];
            while (($row = fgetcsv($csvFile, 1000, ",")) !== FALSE) 
            {
                $data[] = $row;
            }
            fclose($csvFile);
            return $data;
        } 
        // Read Excel file
        elseif (in_array($fileExtension, ['xls', 'xlsx'])) 
        {
            $spreadsheet = IOFactory::load($filePath);
            $data = $spreadsheet->getActiveSheet()->toArray();
            return $data;
        } 
        else 
        {
            echo "Error: Unsupported file type\n";
            exit(1);
        }
    }
}
