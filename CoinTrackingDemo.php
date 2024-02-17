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
            $currTransactionFamiliy = new TransactionFamily($this->getSmallestChange($sheetData));
            if ($currTransactionFamiliy->getTradeChangeRow() === null) 
            {
                $currTransactionFamiliy->setTradeChangeRow($this->getSmallestChange($sheetData, null, "Buy"));
                $currTransactionFamiliy->setTradeChangeRowPair($this->getSmallestChange($sheetData, $currTransactionFamiliy->getTradeChangeRow(), "Buy"));
            }
            else
            {
                $currTransactionFamiliy->setTradeChangeRowPair($this->getSmallestChange($sheetData, $currTransactionFamiliy->getTradeChangeRow()));
            }
            // trade transactions also have a fee transaction
            $currTransactionFamiliy->setFeeChangeRow($this->getSmallestChange($sheetData, null, "Fee"));

            // if this transaction is not a trade transaction then it is a deposit, referral or mining transaction
            // and then append the "processed" transaction from that group to the json var
            if (!$currTransactionFamiliy->isTransactionTrade()) 
            {
                $currTransactionFamiliy->setReferralChangeRow($this->getSmallestChange($sheetData, null, "Referral Kickback"));
                $this->appendToJSON($currTransactionFamiliy->getTransactionByType($currTransactionFamiliy->getReferralChangeRow(), "Reward/Bonus"));

                $currTransactionFamiliy->setDepositChangeRow($this->getSmallestChange($sheetData, null, "Deposit"));
                $this->appendToJSON($currTransactionFamiliy->getTransactionByType($currTransactionFamiliy->getDepositChangeRow(), "Deposit"));

                $currTransactionFamiliy->setMinningChangeRow($this->getSmallestChange($sheetData, null, "Super BNB Mining"));
                $this->appendToJSON($currTransactionFamiliy->getTransactionByType($currTransactionFamiliy->getMinningChangeRow(), "Mining"));
            }
            else
            {
                $this->appendToJSON($currTransactionFamiliy->getTransactionByType($currTransactionFamiliy->getTradeChangeRow(), "Trade", $currTransactionFamiliy->getTradeChangeRowPair()));
                $this->appendToJSON($currTransactionFamiliy->getTransactionByType($currTransactionFamiliy->getFeeChangeRow(), "Other fee"));
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
    public function getSmallestChange($sheetData, $smallestChangeRow = null, $type = "Sell")
    {
        $smallestChange = PHP_INT_MAX;
        $smallestChangeRowResult = null;
        foreach ($sheetData as $row) 
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