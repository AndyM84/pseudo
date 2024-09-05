<?php
namespace Pseudo;

class Pdo extends \PDO
{
    private $mockedQueries;
    private $inTransaction = false;
    private $queryLog;

    public function prepare(string $query, array $options = null): \PDOStatement|false
    {
        $result = $this->mockedQueries->getResult($query);
        return new PdoStatement($result, $this->queryLog, $query);
    }

    public function beginTransaction(): bool
    {
        if (!$this->inTransaction) {
            $this->inTransaction = true;
            return true;
        }
        return false;
        // not yet implemented
    }

    public function commit(): bool
    {
        if ($this->inTransaction()) {
            $this->inTransaction = false;
            return true;
        }
        return false;
        // not yet implemented
    }

    public function rollBack(): bool
    {
        if ($this->inTransaction()) {
            $this->inTransaction = false;
            return true;
        }
        // not yet implemented
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function setAttribute(int $attribute, mixed $value): bool
    {
        // not yet implemented
    }

    public function exec(string $statement): int|false
    {
        $result = $this->query($statement);
        if ($result) {
            return $result->rowCount();
        }
        return 0;
    }

    /**
     * @param string $query
     * @param int|null $fetchMode
     * @param mixed ...$fetchModeArgs
     * @return PdoStatement
     */
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): \PDOStatement|false
    {
        if ($this->mockedQueries->exists($query)) {
            $result = $this->mockedQueries->getResult($query);
            if ($result) {
                $this->queryLog->addQuery($query);
                $statement = new PdoStatement();
                $statement->setResult($result);
                return $statement;
            }
        }
    }

    /**
     * @param null $name
     * @return int
     */
    public function lastInsertId(?string $name = null): string|false
    {
        $result = $this->getLastResult();
        if ($result) {
            return $result->getInsertId();
        }
        return 0;
    }

    /**
     * @return result
     */
    private function getLastResult()
    {
        $lastQuery = $this->queryLog[count($this->queryLog) - 1];
        $result = $this->mockedQueries->getResult($lastQuery);
        return $result;
    }

    public function errorCode(): ?string
    {
        return null;
    }

    public function errorInfo(): array
    {
        return [];
    }

    public function getAttribute(int $attribute): mixed
    {
        return null;
    }

    public function quote(string $string, int $type = PDO::PARAM_STR): string|false
    {
        return $string;
    }

    /**
     * @param ResultCollection $collection
     */
    public function __construct(ResultCollection $collection = null) 
    {
        $this->mockedQueries = $collection ?: new ResultCollection();
        $this->queryLog = new QueryLog();
    }

    /**
     * @param string $filePath
     */
    public function save($filePath)
    {
        file_put_contents($filePath, serialize($this->mockedQueries));
    }

    /**
     * @param $filePath
     */
    public function load($filePath)
    {
        $this->mockedQueries = unserialize(file_get_contents($filePath));
    }

    /**
     * @param $sql
     * @param null $expectedResults
     * @param null $params
     */
    public function mock($sql, $expectedResults = null, $params = null)
    {
        $this->mockedQueries->addQuery($sql, $expectedResults, $params);
    }

    /**
     * @return ResultCollection
     */
    public function getMockedQueries()
    {
        return $this->mockedQueries;
    }
}
