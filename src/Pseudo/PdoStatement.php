<?php
namespace Pseudo;

class PdoStatement extends \PDOStatement
{

    /**
     * @var Result;
     */
    private $result;
    private $fetchMode = \PDO::FETCH_BOTH; //DEFAULT FETCHMODE
    private $boundParams = [];
    private $boundColumns = [];

    /**
     * @var QueryLog
     */
    private $queryLog;

    /**
     * @var string
     */
    private $statement;

    /**
     * @param Querylog $queryLog
     * @param string $statement
     * @param Result|null $result
     */
    public function __construct($result = null, QueryLog $queryLog = null, $statement = null)
    {
        if (!($result instanceof Result)) {
            $result = new Result();
        }
        $this->result = $result;
        if (!($queryLog instanceof QueryLog)) {
            $queryLog = new QueryLog();
        }
        $this->queryLog = $queryLog;
        $this->statement = $statement;
    }

    public function setResult(Result $result)
    {
        $this->result = $result;
    }

    /**
     * @param array|null $input_parameters
     * @return bool
     */
    public function execute(?array $params = null): bool
    {
        $input_parameters = array_merge((array)$params, $this->boundParams);
        try {
            $this->result->setParams($input_parameters, !empty($this->boundParams));
            $success = (bool) $this->result->getRows($input_parameters ?: []);
            $this->queryLog->addQuery($this->statement);
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }

    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        // scrolling cursors not implemented
        $row = $this->result->nextRow();
        if ($row) {
            return $this->proccessFetchedRow($row, $mode);
        }
        return false;
    }

    public function bindParam(string|int $param, mixed &$var, int $type = PDO::PARAM_STR, int $maxLength = null, mixed $driverOptions = null): bool
    {
        $this->boundParams[$param] =&$var;
        return true;
    }

    public function bindColumn(string|int $column, mixed &$var, int $type = PDO::PARAM_STR, int $maxLength = null, mixed $driverOptions = null): bool
    {
        $this->boundColumns[$column] =&$var;
        return true;
    }

    public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        $this->boundParams[$param] = $value;
        return true;
    }

    public function rowCount(): int
    {
        return $this->result->getAffectedRowCount();
    }

    public function fetchColumn(int $column = 0): mixed
    {
        $row = $this->result->nextRow();
        if ($row) {
            $row = $this->proccessFetchedRow($row, \PDO::FETCH_NUM);
            return $row[$column];
        }
        return false;
    }

    public function fetchAll(int $mode = PDO::FETCH_BOTH, mixed ...$args): array
    {
        $rows = $this->result->getRows() ?: [];
        $returnArray = [];
        foreach ($rows as $row) {
            $returnArray[] = $this->proccessFetchedRow($row, $mode);
        }
        return $returnArray;
    }

    private function proccessFetchedRow($row, $fetchMode)
    {
        $i = 0;
        switch ($fetchMode ?: $this->fetchMode) {
            case \PDO::FETCH_BOTH:
                $returnRow = [];
                $keys = array_keys($row);
                $c = 0;
                foreach ($keys as $key) {
                    $returnRow[$key] = $row[$key];
                    $returnRow[$c++] = $row[$key];
                }
                return $returnRow;
            case \PDO::FETCH_ASSOC:
                return $row;
            case \PDO::FETCH_NUM:
                return array_values($row);
            case \PDO::FETCH_OBJ:
                return (object) $row;
            case \PDO::FETCH_BOUND:
                if ($this->boundColumns) {
                    if ($this->result->isOrdinalArray($this->boundColumns)) {
                        foreach ($this->boundColumns as &$column) {
                            $column = array_values($row)[++$i];
                        }
                    } else {

                        foreach ($this->boundColumns as $columnName => &$column) {
                            $column = $row[$columnName];
                        }
                    }
                    return true;
                }
                break;
            case \PDO::FETCH_COLUMN:
               $returnRow = array_values( $row );
               return $returnRow[0];
        }
        return null;
    }

    /**
     * @param string|null $class_name
     * @param array|null $ctor_args
     * @return bool|mixed
     */
    public function fetchObject(?string $class = "stdClass", array $constructorArgs = []): object|false
    {
        $row = $this->result->nextRow();
        if ($row) {
            $reflect  = new \ReflectionClass($class);
            $obj = $reflect->newInstanceArgs($constructorArgs ?: []);
            foreach ($row as $key => $val) {
                $obj->$key = $val;
            }
            return $obj;
        }
        return false;
    }

    /**
     * @return string
     */
    public function errorCode(): ?string
    {
        return $this->result->getErrorCode();
    }

    /**
     * @return string
     */
    public function errorInfo(): array
    {
        return [$this->result->getErrorInfo()];
    }

    /**
     * @return int
     */
    public function columnCount(): int
    {
        $rows = $this->result->getRows();
        if ($rows) {
            $row = array_shift($rows);
            return count(array_keys($row));
        }
        return 0;
    }

    /**
     * @param int $mode
     * @param mixed ...$args
     * @return bool|int
     */
    public function setFetchMode(int $mode, mixed ...$params)
    {
        $r = new \ReflectionClass(new Pdo());
        $constants = $r->getConstants();
        $constantNames = array_keys($constants);
        $allowedConstantNames = array_filter($constantNames, function($val) {
            return strpos($val, 'FETCH_') === 0;
        });
        $allowedConstantVals = [];
        foreach ($allowedConstantNames as $name) {
            $allowedConstantVals[] = $constants[$name];
        }

        if (in_array($mode, $allowedConstantVals)) {
            $this->fetchMode = $mode;
            return 1;
        }
        return false;
    }

    public function nextRowset(): bool
    {
        // not implemented
    }

    public function closeCursor(): bool
    {
        // not implemented
    }

    public function debugDumpParams(): ?bool
    {
        // not implemented
    }


    // some functions make no sense when not actually talking to a database, so they are not implemented

    public function setAttribute(int $attribute, mixed $value): bool
    {
        // not implemented
    }

    public function getAttribute(int $name): mixed
    {
        // not implemented
    }

    public function getColumnMeta(int $column): array|false
    {
        // not implemented
    }

    public function getBoundParams()
    {
        return $this->boundParams;
    }
}
