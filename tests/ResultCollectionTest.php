<?php

	use PHPUnit\Framework\TestCase;

	class ResultCollectionTest extends TestCase
{
    public function testGetResultWithoutMocking()
    {
        $r = new Pseudo\ResultCollection();
				$this->expectException("Pseudo\\Exception");
        $r->getResult("SELECT 1");
    }
    
    public function testDebuggingRawQueries()
    {
        $message = null;
        $r = new Pseudo\ResultCollection();
        try {
            $r->getResult('SELECT 123');
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
        $this->assertMatchesRegularExpression('/SELECT 123/', $message);
    }
}