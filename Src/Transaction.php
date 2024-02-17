<?php

class Transaction
{
    private $TradeChangeRow;
    private $TradeChangeRowPair;
    private $FeeChangeRow;
    private $ReferralChangeRow;
    private $DepositChangeRow;
    private $MinningChangeRow;

    public function __construct($TradeChangeRow = null, $TradeChangeRowPair = null, $FeeChangeRow = null, $ReferralChangeRow = null, $DepositChangeRow = null, $MinningChangeRow = null)
    {
        $this->TradeChangeRow = $TradeChangeRow;
        $this->TradeChangeRowPair = $TradeChangeRowPair;
        $this->FeeChangeRow = $FeeChangeRow;
        $this->ReferralChangeRow = $ReferralChangeRow;
        $this->DepositChangeRow = $DepositChangeRow;
        $this->MinningChangeRow = $MinningChangeRow;
    }

    public function getTradeChangeRow()
    {
        return $this->TradeChangeRow;
    }

    public function setTradeChangeRow($TradeChangeRow)
    {
        $this->TradeChangeRow = $TradeChangeRow;
    }

    public function getTradeChangeRowPair()
    {
        return $this->TradeChangeRowPair;
    }

    public function setTradeChangeRowPair($TradeChangeRowPair)
    {
        $this->TradeChangeRowPair = $TradeChangeRowPair;
    }

    public function getFeeChangeRow()
    {
        return $this->FeeChangeRow;
    }

    public function setFeeChangeRow($FeeChangeRow)
    {
        $this->FeeChangeRow = $FeeChangeRow;
    }

    public function getReferralChangeRow()
    {
        return $this->ReferralChangeRow;
    }

    public function setReferralChangeRow($ReferralChangeRow)
    {
        $this->ReferralChangeRow = $ReferralChangeRow;
    }

    public function getDepositChangeRow()
    {
        return $this->DepositChangeRow;
    }

    public function setDepositChangeRow($DepositChangeRow)
    {
        $this->DepositChangeRow = $DepositChangeRow;
    }

    public function getMinningChangeRow()
    {
        return $this->MinningChangeRow;
    }

    public function setMinningChangeRow($MinningChangeRow)
    {
        $this->MinningChangeRow = $MinningChangeRow;
    }

    public function isTransactionTrade()
    {
        return $this->TradeChangeRow !== null && $this->TradeChangeRowPair !== null && $this->FeeChangeRow !== null;
    }

    // return not null rows
    public function getRows()
    {
        $rows = 
        [
            $this->TradeChangeRow !== null ? $this->TradeChangeRow : null,
            $this->TradeChangeRowPair !== null ? $this->TradeChangeRowPair : null,
            $this->FeeChangeRow !== null ? $this->FeeChangeRow : null,
            $this->ReferralChangeRow !== null ? $this->ReferralChangeRow : null,
            $this->DepositChangeRow !== null ? $this->DepositChangeRow : null,
            $this->MinningChangeRow !== null ? $this->MinningChangeRow : null,
        ];

        return array_filter($rows, function ($row) 
        {
            return $row !== null;
        });
    }

    public function getTransaction($row, $type, $pairRow = null)
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
        return $jsonEntry;
    }

    private function setCurrencyAmountAndEur(&$jsonEntry, $rowData, $operationType, $override = false)
    {
        $currencyKey = $operationType . "_currency";
        $amountKey = $operationType;
        $eurKey = $operationType . "_eur";

        // If override is true, it will always set the values, otherwise, it sets them based on operationType
        if ($override || !isset($jsonEntry[$currencyKey])) 
        {
            $jsonEntry[$currencyKey] = $rowData[4];
            $jsonEntry[$amountKey] = abs(floatval($rowData[5]));
            if ($rowData[4] !== "EUR")
            {
                $coinPrice = APIUtils::fetchCoinPriceInEuro($rowData[4], $rowData[1]);
                $jsonEntry[$eurKey] = is_float($coinPrice) ? $coinPrice * $jsonEntry[$amountKey] : $coinPrice;
            }
            
        }
    }


    public function formatValue(&$jsonEntry)
    {
        isset($jsonEntry["sell_eur"]) && is_float($jsonEntry["sell_eur"]) ? $jsonEntry["sell_eur"] = (float)number_format($jsonEntry["sell_eur"], 8, '.', '') : null;
        isset($jsonEntry["buy_eur"]) && is_float($jsonEntry["buy_eur"]) ? $jsonEntry["buy_eur"] = (float)number_format($jsonEntry["buy_eur"], 8, '.', '') : null;
        isset($jsonEntry["sell"]) && is_float($jsonEntry["sell"]) ? $jsonEntry["sell"] = (float)number_format($jsonEntry["sell"], 8, '.', '') : null;
        isset($jsonEntry["buy"]) && is_float($jsonEntry["buy"]) ? $jsonEntry["buy"] = (float)number_format($jsonEntry["buy"], 8, '.', '') : null;
    }
}
?>