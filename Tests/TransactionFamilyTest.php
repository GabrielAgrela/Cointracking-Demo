<?php

use PHPUnit\Framework\TestCase;

require_once 'Src/TransactionFamily.php';

class TransactionFamilyTest extends TestCase
{
    private $transactionFamily;

    protected function setUp(): void
    {
        $this->transactionFamily = new TransactionFamily();
    }

    public function testSetTransaction()
    {
        $transaction = ["", "", "", "Sell", "", 10];
        $this->transactionFamily->setTransaction($transaction);
        $this->assertEquals("Trade", $this->transactionFamily->getTransaction("Trade")[3]);

        $this->setUp();
        $transaction = ["", "", "", "Buy", "", 10];
        $this->transactionFamily->setTransaction($transaction);
        $this->assertEquals("Trade", $this->transactionFamily->getTransaction("Trade")[3]);

        $this->setUp();
        $transaction = ["", "", "", "Fee", "", 10];
        $this->transactionFamily->setTransaction($transaction);
        $this->assertEquals("Other fee", $this->transactionFamily->getTransaction("Other fee")[3]);

        $this->setUp();
        $transaction = ["", "", "", "Referral Kickback", "", 10];
        $this->transactionFamily->setTransaction($transaction);
        $this->assertEquals("Reward/Bonus", $this->transactionFamily->getTransaction("Reward/Bonus")[3]);

        $this->setUp();
        $transaction = ["", "", "", "Super BNB Mining", "", 10];
        $this->transactionFamily->setTransaction($transaction);
        $this->assertEquals("Mining", $this->transactionFamily->getTransaction("Mining")[3]);

        $this->setUp();
        $transaction = ["", "", "", "Random Thing", "", -10];
        $this->transactionFamily->setTransaction($transaction);
        $this->assertEquals("Random Thing", $this->transactionFamily->getTransaction("Random Thing")[3]);
        $this->assertEquals(10, $this->transactionFamily->getTransaction("Random Thing")[5]);

    }

    public function testGetTransaction()
    {
        $transaction = ["", "", "", "Sell", "", -10];
        $this->transactionFamily->setTransaction($transaction);
        $result = $this->transactionFamily->getTransaction("Trade");
        $this->assertEquals(["", "", "", "Trade", "", 10], $result);
    }

    public function testGetAllTransactions()
    {
        $transaction1 = ["", "", "", "Sell", "", -10];
        $transaction2 = ["", "", "", "Other fee", "", 5];
        $this->transactionFamily->setTransaction($transaction1);
        $this->transactionFamily->setTransaction($transaction2);
        $result = $this->transactionFamily->getAllTransactions();
        $this->assertEquals([["", "", "", "Trade", "", 10], ["", "", "", "Other fee", "", 5]], $result);
    }

    public function testIsTransactionTrade()
    {
        $transaction1 = ["", "", "", "Trade", "", 10];
        $transaction2 = ["", "", "", "Trade", "", 5];
        $transaction3 = ["", "", "", "Trade", "", 20];
        $this->transactionFamily->setTransaction($transaction1);
        $this->transactionFamily->setTransaction($transaction2);
        $this->transactionFamily->setTransaction($transaction3);
        $result = $this->transactionFamily->isTransactionTrade();
        $this->assertTrue($result);
    }

    // too basic, add more robust tests
    public function testFormatValue()
    {
        $jsonEntry = [
            "sell_eur" => 1234.5678912345,
            "buy_eur" => 1234.5678912345,
            "sell" => 1234.5678912345,
            "buy" => 1234.5678912345,
        ];

        $this->transactionFamily->formatValue($jsonEntry);

        $this->assertEquals(1234.56789123, $jsonEntry["sell_eur"]);
        $this->assertEquals(1234.56789123, $jsonEntry["buy_eur"]);
        $this->assertEquals(1234.56789123, $jsonEntry["sell"]);
        $this->assertEquals(1234.56789123, $jsonEntry["buy"]);
    }


    // Test transaction building
}

?>