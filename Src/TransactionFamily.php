<?php

class TransactionFamily
{
    // transactions from the same sheet's group 
    private $transactions = [];

    // setter that translates type and amount of the transaction
    public function setTransaction($transaction)
    {
        if ($transaction === null)
            return;
        switch ($transaction[3]) 
        {
            case "Sell":
                $transaction[3] = "Trade";
                break;
            case "Buy":
                $transaction[3] = "Trade";
                break;
            case "Fee":
                $transaction[3] = "Other fee";
                break;
            case "Referral Kickback":
                $transaction[3] = "Reward/Bonus";
                break;
            case "Super BNB Mining":
                $transaction[3] = "Mining";
                break;
        }
        $transaction[5] = abs($transaction[5]);
        $this->transactions[] = $transaction;
    }

    // getter that returns a transaction or an array of transactions of a specific type
    public function getTransaction($type)
    {
        $transactions = [];
        foreach ($this->transactions as $transaction) 
        {
            if ($transaction[3] === $type) 
            {
                $transactions[] = $transaction;
            }
        }
        // return null if no transaction of that type is found return array if multiple transactions are found else return the transaction
        return count($transactions) === 0 ? null : (count($transactions) === 1 ? $transactions[0] : $transactions);
    }

    // getter that returns all transactions
    public function getAllTransactions()
    {
        return $this->transactions;
    }

    // check if the transaction is a trade transaction
    public function isTransactionTrade()
    {
        return count($this->transactions) === 3;
    }

    // return transaction by constructing a json entry from the rows depending on the type of transaction
    public function buildTransactionJson()
    {
        if (count($this->transactions) === 0)
            return null;

        $jsonEntry = 
        [
            "time" => strtotime($this->transactions[0][1]),
            "type" => $this->transactions[0][3],
        ];

        // the first transaction of a family will always follow this pattern, and is then removed from this family
        $this->transactions[0][3] === "Other fee" ? $this->setCurrencyAmountAndEur($jsonEntry, $this->transactions[0], 'sell') : $this->setCurrencyAmountAndEur($jsonEntry, $this->transactions[0], 'buy');

        // if there is still 2 other transactions, we can assume that it is a trade transaction and we need to format the json entry accordingly
        count($this->transactions) === 2 ? $this->setCurrencyAmountAndEur($jsonEntry, $this->transactions[0], 'sell', true) : null;

        $this->formatValue($jsonEntry);
        
        echo "\nTransaction Processed";
        // remove the processed transactions from the group
        return $jsonEntry;
    }

    // helper function to set the currency, amount and eur values in the json entry
    private function setCurrencyAmountAndEur(&$jsonEntry, $rowData, $operationType, $override = false)
    {
        $currencyKey = $operationType . "_currency";
        $amountKey = $operationType;
        $eurKey = $operationType . "_eur";

        // if the currency is not set or if the override flag is set then set the currency, amount and eur values
        if ($override || !isset($jsonEntry[$currencyKey])) 
        {
            $jsonEntry[$currencyKey] = $rowData[4];
            $jsonEntry[$amountKey] = abs(floatval($rowData[5]));

            // if the currency is not EUR then fetch the coin price in EUR
            if ($rowData[4] !== "EUR")
            {
                $coinPrice = APIUtils::fetchCoinPriceInEuro($rowData[4], $rowData[1]);
                $jsonEntry[$eurKey] = is_float($coinPrice) ? $coinPrice * $jsonEntry[$amountKey] : $coinPrice;
            }
            
        }
        
        // remove transaction[0]
        array_shift($this->transactions);
    }

    // helper function to format the values in the json entry
    public function formatValue(&$jsonEntry)
    {
        isset($jsonEntry["sell_eur"]) && is_float($jsonEntry["sell_eur"]) ? $jsonEntry["sell_eur"] = (float)number_format($jsonEntry["sell_eur"], 8, '.', '') : null;
        isset($jsonEntry["buy_eur"]) && is_float($jsonEntry["buy_eur"]) ? $jsonEntry["buy_eur"] = (float)number_format($jsonEntry["buy_eur"], 8, '.', '') : null;
        isset($jsonEntry["sell"]) && is_float($jsonEntry["sell"]) ? $jsonEntry["sell"] = (float)number_format($jsonEntry["sell"], 8, '.', '') : null;
        isset($jsonEntry["buy"]) && is_float($jsonEntry["buy"]) ? $jsonEntry["buy"] = (float)number_format($jsonEntry["buy"], 8, '.', '') : null;
    }
}
?>