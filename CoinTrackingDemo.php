<?php
require_once 'Src/FileUtils.php';
require_once 'Src/APIUtils.php';
require_once 'Src/TransactionFamily.php';
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

        // process transactions per group
        foreach ($data as $utcTime => $chunk) 
        {
            $this->processGroup($chunk);
        }

        // print transactions as json
        echo json_encode($this->json, JSON_PRETTY_PRINT);
    }


    public function processGroup($sheetData)
    {
        // when looking for a transaction, priority given to smallest "change" per type of transaction
        while (count($sheetData) > 0)
        {
            $transactionFamily = new TransactionFamily();
            $transactionFamily->setTransaction($this->getLowestTransactionInGroup($sheetData, null, "Sell"));
            if ($transactionFamily->getTransaction("Trade") === null) 
            {
                $transactionFamily->setTransaction($this->getLowestTransactionInGroup($sheetData, null, "Buy"));
                $transactionFamily->setTransaction($this->getLowestTransactionInGroup($sheetData, $transactionFamily->getTransaction("Trade"), "Buy"));
            }
            else
            {
                $transactionFamily->setTransaction($this->getLowestTransactionInGroup($sheetData, $transactionFamily->getTransaction("Trade"), "Sell"));
            }
            $transactionFamily->setTransaction($this->getLowestTransactionInGroup($sheetData, null, "Fee"));

            if (!$transactionFamily->isTransactionTrade()) 
            {
                $transactionFamily->setTransaction($this->getLowestTransactionInGroup($sheetData, null, "Referral Kickback"));
                $transactionFamily->setTransaction($this->getLowestTransactionInGroup($sheetData, null, "Deposit"));
                $transactionFamily->setTransaction($this->getLowestTransactionInGroup($sheetData, null, "Super BNB Mining"));
            }
            $this->appendToJSON($transactionFamily);
        }
    }

    public function appendToJSON($transactionFamily)
    { 
        while (count($transactionFamily->getAllTransactions()) > 0) 
        {
            $transaction = $transactionFamily->buildTransactionJson();
            if ($transaction !== null) 
            {
                $this->json[] = $transaction;
            }
        }
        
    }

    public function removeRows(&$sheetData, $currTransactionFamiliy)
    {
        foreach ($sheetData as $key => $row) 
        {
            foreach ($currTransactionFamiliy->getAllTransactions() as $rowToRemove) 
            {
                if ($row === $rowToRemove) 
                {
                    unset($sheetData[$key]);
                }
            }
        } 
    }

    // get the smallest row from a group given a type of transaction and a row to ignore (in case its the pair of a trade transaction)
    public function getLowestTransactionInGroup(&$sheetData, $smallestTransaction = null, $type = "Sell")
    {
        $smallestChange = PHP_INT_MAX;
        $smallestTransactionResult = null;
        foreach ($sheetData as $row) 
        {
            if (isset($row[3]) && $row[3] == $type) 
            {
                if ($smallestTransaction !== null && $row[4] == $smallestTransaction[4]) 
                {
                    continue;
                }
                $change = abs(isset($row[5]) ? (float)$row[5] : null);
                if ($change < $smallestChange) 
                {
                    $smallestChange = $change;
                    $smallestTransactionResult = $row;
                }
            }
        }
        if ($smallestTransactionResult !== null)
            unset($sheetData[array_search($smallestTransactionResult, $sheetData)]);
        return $smallestTransactionResult;
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