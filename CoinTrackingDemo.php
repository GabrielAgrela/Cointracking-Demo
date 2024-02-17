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
            // create a new transaction group looking for a trade transaction (figure out if its a buy or sell transaction) then also get its pair
            $currTransactionFamiliy = new TransactionFamily($this->getLowestTransactionInGroup($sheetData));
            if ($currTransactionFamiliy->getTradeTransaction() === null) 
            {
                $currTransactionFamiliy->setTradeTransaction($this->getLowestTransactionInGroup($sheetData, null, "Buy"));
                $currTransactionFamiliy->setTradeTransactionPair($this->getLowestTransactionInGroup($sheetData, $currTransactionFamiliy->getTradeTransaction(), "Buy"));
            }
            else
            {
                $currTransactionFamiliy->setTradeTransactionPair($this->getLowestTransactionInGroup($sheetData, $currTransactionFamiliy->getTradeTransaction()));
            }
            // trade transactions also have a fee transaction
            $currTransactionFamiliy->setFeeTransaction($this->getLowestTransactionInGroup($sheetData, null, "Fee"));

            // if this transaction is not a trade transaction then it is a deposit, referral or mining transaction
            // and then append the "processed" transaction from that group to the json var
            if (!$currTransactionFamiliy->isTransactionTrade()) 
            {
                $currTransactionFamiliy->setReferralTransaction($this->getLowestTransactionInGroup($sheetData, null, "Referral Kickback"));
                $this->appendToJSON($currTransactionFamiliy->getTransactionByType($currTransactionFamiliy->getReferralTransaction(), "Reward/Bonus"));

                $currTransactionFamiliy->setDepositTransaction($this->getLowestTransactionInGroup($sheetData, null, "Deposit"));
                $this->appendToJSON($currTransactionFamiliy->getTransactionByType($currTransactionFamiliy->getDepositTransaction(), "Deposit"));

                $currTransactionFamiliy->setMinningTransaction($this->getLowestTransactionInGroup($sheetData, null, "Super BNB Mining"));
                $this->appendToJSON($currTransactionFamiliy->getTransactionByType($currTransactionFamiliy->getMinningTransaction(), "Mining"));
            }
            else
            {
                $this->appendToJSON($currTransactionFamiliy->getTransactionByType($currTransactionFamiliy->getTradeTransaction(), "Trade", $currTransactionFamiliy->getTradeTransactionPair()));
                $this->appendToJSON($currTransactionFamiliy->getTransactionByType($currTransactionFamiliy->getFeeTransaction(), "Other fee"));
            }

            // remove the processed transactions from the group
            $this->removeRows($sheetData, $currTransactionFamiliy);        
        }
    }

    public function appendToJSON($transaction)
    {
        if ($transaction !== null) 
        {
            $this->json[] = $transaction;
        }
    }

    public function removeRows(&$sheetData, $currTransactionFamiliy)
    {
        foreach ($sheetData as $key => $row) 
        {
            foreach ($currTransactionFamiliy->getRows() as $rowToRemove) 
            {
                if ($row === $rowToRemove) 
                {
                    unset($sheetData[$key]);
                }
            }
        } 
    }

    // get the smallest row from a group given a type of transaction and a row to ignore (in case its the pair of a trade transaction)
    public function getLowestTransactionInGroup($sheetData, $smallestTransaction = null, $type = "Sell")
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