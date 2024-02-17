<?php

class TransactionFamily
{
    private $TradeTransaction;
    private $TradeTransactionPair;
    private $FeeTransaction;
    private $ReferralTransaction;
    private $DepositTransaction;
    private $MinningTransaction;

    public function __construct($TradeTransaction = null, $TradeTransactionPair = null, $FeeTransaction = null, $ReferralTransaction = null, $DepositTransaction = null, $MinningTransaction = null)
    {
        $this->TradeTransaction = $TradeTransaction;
        $this->TradeTransactionPair = $TradeTransactionPair;
        $this->FeeTransaction = $FeeTransaction;
        $this->ReferralTransaction = $ReferralTransaction;
        $this->DepositTransaction = $DepositTransaction;
        $this->MinningTransaction = $MinningTransaction;
    }

    // getters and setters
    public function getTradeTransaction()
    {
        return $this->TradeTransaction;
    }

    public function setTradeTransaction($TradeTransaction)
    {
        $this->TradeTransaction = $TradeTransaction;
    }

    public function getTradeTransactionPair()
    {
        return $this->TradeTransactionPair;
    }

    public function setTradeTransactionPair($TradeTransactionPair)
    {
        $this->TradeTransactionPair = $TradeTransactionPair;
    }

    public function getFeeTransaction()
    {
        return $this->FeeTransaction;
    }

    public function setFeeTransaction($FeeTransaction)
    {
        $this->FeeTransaction = $FeeTransaction;
    }

    public function getReferralTransaction()
    {
        return $this->ReferralTransaction;
    }

    public function setReferralTransaction($ReferralTransaction)
    {
        $this->ReferralTransaction = $ReferralTransaction;
    }

    public function getDepositTransaction()
    {
        return $this->DepositTransaction;
    }

    public function setDepositTransaction($DepositTransaction)
    {
        $this->DepositTransaction = $DepositTransaction;
    }

    public function getMinningTransaction()
    {
        return $this->MinningTransaction;
    }

    public function setMinningTransaction($MinningTransaction)
    {
        $this->MinningTransaction = $MinningTransaction;
    }

    // check if the transaction is a trade transaction
    public function isTransactionTrade()
    {
        return $this->TradeTransaction !== null && $this->TradeTransactionPair !== null && $this->FeeTransaction !== null;
    }

    // return not null rows
    public function getRows()
    {
        $rows = 
        [
            $this->TradeTransaction !== null ? $this->TradeTransaction : null,
            $this->TradeTransactionPair !== null ? $this->TradeTransactionPair : null,
            $this->FeeTransaction !== null ? $this->FeeTransaction : null,
            $this->ReferralTransaction !== null ? $this->ReferralTransaction : null,
            $this->DepositTransaction !== null ? $this->DepositTransaction : null,
            $this->MinningTransaction !== null ? $this->MinningTransaction : null,
        ];

        return array_filter($rows, function ($row) 
        {
            return $row !== null;
        });
    }

    // return transaction by constructing a json entry from the rows depending on the type of transaction
    public function getTransactionByType($row, $type, $pairRow = null)
    {
        if ($row === null)
            return null;

        $jsonEntry = 
        [
            "time" => strtotime($row[1]),
            "type" => $type,
        ];

        $type === "Other fee" ? $this->setCurrencyAmountAndEur($jsonEntry, $row, 'sell') : $this->setCurrencyAmountAndEur($jsonEntry, $row, 'buy');

        $pairRow !== null ? $this->setCurrencyAmountAndEur($jsonEntry, $pairRow, 'sell', true) : null;

        $this->formatValue($jsonEntry);
        echo "One transaction processed\n";
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