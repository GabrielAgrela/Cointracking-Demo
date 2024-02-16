<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require_once __DIR__ . '/../vendor/autoload.php';

class FileUtils
{
    public static function groupData($csvData)
    {
        $groupedData = [];
        foreach ($csvData as $row) {
            $utcTime = $row[1];
            if (!isset($groupedData[$utcTime])) {
                $groupedData[$utcTime] = [];
            }
            $groupedData[$utcTime][] = $row;
        }
        return $groupedData;
    }

    public static function checkFile($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            echo "Error: File not found or not readable\n";
            exit(1);
        }
    }

    public static function readFile($filePath)
    {
        // Determine file type by extension
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($fileExtension == 'csv') {
            // Read CSV file
            $csvFile = fopen($filePath, 'r');
            $data = [];
            while (($row = fgetcsv($csvFile, 1000, ",")) !== FALSE) {
                $data[] = $row;
            }
            fclose($csvFile);
            return $data;
        } elseif (in_array($fileExtension, ['xls', 'xlsx'])) {
            // Read Excel file
            $spreadsheet = IOFactory::load($filePath);
            $data = $spreadsheet->getActiveSheet()->toArray();
            return $data;
        } else {
            echo "Error: Unsupported file type\n";
            exit(1);
        }
    }
}
