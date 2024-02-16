<?php

class FileUtils
{
    public static function groupData($csvData)
    {
        $groupedData = [];
        foreach ($csvData as $row) 
        {
            $utcTime = $row[1];
            if (!isset($groupedData[$utcTime])) 
            {
                $groupedData[$utcTime] = [];
            }
            $groupedData[$utcTime][] = $row;
        }
        $csvData = $groupedData;
        return $csvData;
    }

    public static function checkFile($filePath) 
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            echo "Error: File not found or not readable\n";
            exit(1);
        }
    }

    public static function readCSV($filePath) 
    {
        $csvFile = fopen($filePath, 'r');
        $csvData = [];

        while (($row = fgetcsv($csvFile, 1000, ",")) !== FALSE) {
            $csvData[] = $row;
        }
        fclose($csvFile);

        return $csvData;
    }

    public static function readXLS($filePath) 
    {
        require 'vendor/autoload.php';

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $xlsData = [];

        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(FALSE); // This loops through all cells,
                                                               // even if a cell value is not set.
                                                               // By default, only cells that have a value set are iterated.
            $cells = [];
            foreach ($cellIterator as $cell) {
                $cells[] = $cell->getValue();
            }
            $xlsData[] = $cells;
        }

        return $xlsData;
    }
    
}
?>