<?php
namespace Pseudo;

class QueryLog implements \IteratorAggregate, \ArrayAccess, \Countable
{
    private $queries = [];

    public function count(): int
    {
        return count($this->queries);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->queries);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->queries[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->queries[$offset];
    }

    public function offsetSet(mixed $offset, mixed$value): void
    {
        $this->queries[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->queries[$offset]);
    }

    public function addQuery($sql)
    {
        $this->queries[] = new ParsedQuery($sql);
    }

    public function getQueries()
    {
        return $this->queries;
    }
}
