<?php

namespace NNV\YQL;

class QueryBuilder
{
    /**
     * @var $select
     */
    private $select;

    /**
     * @var $from
     */
    private $from;

    /**
     * @var $where
     */
    private $where;

    /**
     * @var $limit
     */
    private $limit;

    /**
     * @var $offset
     */
    private $offset;

    public function __construct()
    {
        $this->initialQueryBuilder();
    }

    /**
     * Select
     *
     * @param  mixed $select String or array column
     * @param  string $alias Alias for column
     * @return self
     */
    public function select($select, $alias = '')
    {
        $fields = '';

        if ($alias) {
            $alias = " AS '{$alias}'";
        }

        if (is_array($select)) {
            $fields = implode(', ', $select);
        } else {
            $fields = " {$select}{$alias} ";
        }


        $this->select[] = $fields;

        return $this;
    }

    /**
     * From
     *
     * @param  string $from From
     * @return self
     */
    public function from($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * And where
     *
     * @param  mixed $column   Column or callback function (for group conditions)
     * @param  mixed $value    Condition value
     * @param  string $operator Condition operator (=, >=, <=, <>, NOT, IN, NOT IN)
     * @return self
     */
    public function where($column, $value = null, $operator = '=')
    {
        if ($column instanceof \Closure) {
            $this->whereOpen();
            $column($this);
            $this->whereClose();

            return $this;
        }

        $value = $this->checkInOrNotInOperator($operator, $value);

        $this->where[] = [
            'query' => sprintf(' %s %s %s ', $column, $operator, $value),
            'type' => 'AND',
        ];

        return $this;
    }

    /**
     * Or wherw
     *
     * @param  mixed $column   Column or callback function (for group condition)
     * @param  mixed $value    Condition value
     * @param  string $operator Condition operator (=, >=, <=, <>, NOT, IN, NOT IN)
     * @return self
     */
    public function orWhere($column, $value = null, $operator = '=')
    {
        if ($column instanceof \Closure) {
            $this->whereOpen('or');
            $column($this);
            $this->whereClose('or');

            return $this;
        }

        $value = $this->checkInOrNotInOperator($operator, $value);

        $this->where[] = [
            'query' => sprintf(' %s %s %s ', $column, $operator, $value),
            'type' => 'OR',
        ];

        return $this;
    }

    /**
     * Order by
     *
     * @param  string $column Column name
     * @param  string $dir    Order by direction (ASC or DESC)
     * @return self
     */
    public function orderBy($column, $dir = 'ASC')
    {
        $this->orderBy[] = "{$column} {$dir}";

        return $this;
    }

    /**
     * Limit
     *
     * @param  int $limit Limit number
     * @return self
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Offset
     *
     * @param  int $offset Offset number
     * @return self
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Get query
     *
     * @return string Query string
     */
    public function getQuery()
    {
        $queryStatement = sprintf('SELECT %s FROM %s %s %s %s %s',
            implode(', ', $this->select),
            $this->from,
            $this->getWhereQuery(),
            $this->getOrderByQuery(),
            $this->getLimitQuery(),
            $this->getOffsetQuery()
        );

        return stripslashes($this->queryBeautifier($queryStatement));
    }

    /**
     * Create new query
     *
     * @return self
     */
    public function newQuery()
    {
        $this->initialQueryBuilder();

        return $this;
    }

    /**
     * Add ( for where group
     *
     * @return void
     */
    private function whereOpen()
    {
        $this->where[] = [
            'query' => '(',
            'type' => ''
        ];
    }

    /**
     * Add ) for where group
     *
     * @return void
     */
    private function whereClose()
    {
        $this->where[] = [
            'query' => ')',
            'type' => ''
        ];
    }

    /**
     * Get IN or NOT IN query string
     *
     * @param  string $operator Operator string
     * @param  mixed $value Value
     * @return string Where string
     */
    private function checkInOrNotInOperator($operator, $value)
    {
        if (strtoupper($operator) === 'IN' || strtoupper($operator) === 'NOT IN') {
            $value = sprintf(" ('%s') ", implode("', '", $value));
        } else {
            $value = "'{$value}'";
        }

        return $value;
    }

    /**
     * Clean query string
     *
     * @param  string $query Query string
     * @return string Query string cleaned
     */
    private function queryBeautifier($query)
    {
        $findWhatArr = array(
            '/\(\s{1,}?and\s{1,}?|\(\s{1,}?or\s{1,}?/i',
            '/\s{1,}?and\s{1,}?\)|\s{1,}?or\s{1,}?\)/i',
            '/and\s{1,}?and/i',
            '/or\s{1,}?or/i',
            '/\s{2,}/',
        );
        $replaceWithArr = array(
            '(',
            ')',
            'AND',
            'OR',
            ' ',
        );

        return preg_replace($findWhatArr, $replaceWithArr, $query);
    }

    /**
     * Reset (initial) all properties
     *
     * @return void
     */
    private function initialQueryBuilder()
    {
        $this->select = [];
        $this->from = '';
        $this->where = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
    }

    /**
     * Get where query string
     *
     * @return string Where query string
     */
    private function getWhereQuery()
    {
        $whereQuery = '';
        $whereNumber = count($this->where);

        if ($whereNumber) {
            $whereQuery .= ' WHERE ';

            foreach ($this->where as $index => $where) {
                if ($whereNumber === ($index + 1)) {
                    $whereQuery .= sprintf(" %s %s ", $where['type'], $where['query']);
                } else {
                    $whereQuery .= sprintf(" %s %s ", $where['query'], $where['type']);
                }
            }
        }

        return $whereQuery;
    }

    /**
     * Get order by query string
     *
     * @return string Order by string
     */
    private function getOrderByQuery()
    {
        $orderByQuery = '';

        if (count($this->orderBy)) {
            $orderByQuery = sprintf(' ORDER BY %s', implode(', ', $this->orderBy));
        }

        return $orderByQuery;
    }

    /**
     * Get limit query string
     *
     * @return string Limit string
     */
    private function getLimitQuery()
    {
        $limitQuery = '';

        if ($this->limit) {
            $limitQuery = " LIMIT {$this->limit} ";
        }

        return $limitQuery;
    }

    /**
     * Get offset query string
     *
     * @return string Offset string
     */
    private function getOffsetQuery()
    {
        $offsetQuery = '';

        if ($this->offset) {
            $offsetQuery = " OFFSET {$this->offset} ";
        }

        return $offsetQuery;
    }
}
