<?php

namespace Platformsh\Client\Tests\Model;


use Platformsh\Client\DataStructure\Collection;
use Platformsh\Client\Model\Transaction;
use Platformsh\Client\Tests\PlatformshTestBase;

class TransactionTest extends PlatformshTestBase
{

    /** @var Transaction */
    private $transaction;

    public function setUp()
    {
        parent::setUp();
        $this->transaction = $this->client->getTransaction(111);
    }

    public function testTransactionGetters()
    {
        $noTransaction = $this->client->getTransaction(123);
        $this->assertNull($noTransaction, 'Requesting a non-existing transaction returns null');

        $this->assertInstanceOf(
            Transaction::class,
            $this->transaction,
            'Requesting an existing transaction returns an instance of Transaction class'
        );

        $transactions = $this->client->getTransactions();
        $this->assertInstanceOf(
            Collection::class,
            $transactions,
            'Requesting multiple transactions returns an instance of Collection class'
        );
        foreach ($transactions as $transaction) {
            $this->assertInstanceOf(Transaction::class, $transaction, 'The collection elements are instances of a Transaction class');
        }
    }

    public function testPropertyGetters()
    {
        $data = $this->data['transactions']['transactions'][0];
        $this->assertEquals($data['id'], $this->transaction->id, 'Transaction ID getter works');
        $this->assertEquals($data['amount'], $this->transaction['amount'], 'Transaction spend getter works');

    }

    public function testOperationAvailable()
    {
        $this->assertFalse($this->transaction->operationAvailable('do-something-ridiculous'), 'Operation unavailable');
    }

    public function testDeletion()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->transaction->delete();
    }

}
