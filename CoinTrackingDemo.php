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
            // instantiate a new transaction family, this will hold all the transactions that are related to each other
            $transactionFamily = new TransactionFamily();

            // priority is done so crescentely by "change" value, and we start by looking for a trade transaction (sell/buy pair and their fee)
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

            // if the transaction family is not a trade transaction, we look for the other types of transactions
            if (!$transactionFamily->isTransactionTrade()) 
            {
                $transactionFamily->setTransaction($this->getLowestTransactionInGroup($sheetData, null, "Referral Kickback"));
                $transactionFamily->setTransaction($this->getLowestTransactionInGroup($sheetData, null, "Deposit"));
                $transactionFamily->setTransaction($this->getLowestTransactionInGroup($sheetData, null, "Super BNB Mining"));
            }

            // after running the algorithm, we append the transaction family to the json
            $this->appendToJSON($transactionFamily);
        }
    }

    // append the transaction family to the json
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