<?php

	use PHPUnit\Framework\TestCase;

	class QueryLogTest extends TestCase
{
    public function testAddQuery()
    {
        $sql = "SELECT foo FROM bar";
        $queryLog = new \Pseudo\QueryLog();
        $queryLog->addQuery($sql);
        $queries = $queryLog->getQueries();
        $this->assertEquals(1, count($queries));
        $this->assertTrue($queries[0]->isEqualTo($sql));
    }
}