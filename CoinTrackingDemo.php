<?php
require_once 'Src/FileUtils.php';
require_once 'Src/APIUtils.php';
require_once 'Src/Transaction.php';
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

        // sort chunk for each utc_time
        foreach ($data as $utcTime => $chunk) 
        {
            $this->processGroup($chunk);
        }
        echo json_encode($this->json, JSON_PRETTY_PRINT);
    }


    public function processGroup($sheetData)
    {
        while (count($sheetData) > 0)
        {
            
            $processHelper = new Transaction($this->getSmallestChange($sheetData));
            if ($processHelper->getTradeChangeRow() === null) 
            {
                $processHelper->setTradeChangeRow($this->getSmallestChange($sheetData, null, "Buy"));
                $processHelper->setTradeChangeRowPair($this->getSmallestChange($sheetData, $processHelper->getTradeChangeRow(), "Buy"));
            }
            else
            {
                $processHelper->setTradeChangeRowPair($this->getSmallestChange($sheetData, $processHelper->getTradeChangeRow()));
            }
            $processHelper->setFeeChangeRow($this->getSmallestChange($sheetData, null, "Fee"));

            if (!$processHelper->isTransactionTrade()) 
            {
                $processHelper->setReferralChangeRow($this->getSmallestChange($sheetData, null, "Referral Kickback"));
                $this->appendToJSON($processHelper->getTransaction($processHelper->getReferralChangeRow(), "Reward/Bonus"));

                $processHelper->setDepositChangeRow($this->getSmallestChange($sheetData, null, "Deposit"));
                $this->appendToJSON($processHelper->getTransaction($processHelper->getDepositChangeRow(), "Deposit"));

                $processHelper->setMinningChangeRow($this->getSmallestChange($sheetData, null, "Super BNB Mining"));
                $this->appendToJSON($processHelper->getTransaction($processHelper->getMinningChangeRow(), "Mining"));
            }
            else
            {
                $this->appendToJSON($processHelper->getTransaction($processHelper->getTradeChangeRow(), "Trade", $processHelper->getTradeChangeRowPair()));
                $this->appendToJSON($processHelper->getTransaction($processHelper->getFeeChangeRow(), "Other fee"));
            }

            $this->removeRows($sheetData, $processHelper);        
        }
    }

    public function appendToJSON($transaction)
    {
        if ($transaction !== null) 
        {
            $this->json[] = $transaction;
        }
    }

    public function removeRows(&$sheetData, $processHelper)
    {
        foreach ($sheetData as $key => $row) 
        {
            foreach ($processHelper->getRows() as $rowToRemove) 
            {
                if ($row === $rowToRemove) 
                {
                    unset($sheetData[$key]);
                }
            }
        } 
    }

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