<?php

namespace NNV\YQL;

class QueryBuilder
{
    private $selects;

    private $from;

    private $andWhere;

    private $orWhere;

    private $orderBy;

    public function __construct()
    {
        $this->initialQueryBuilder();
    }

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


        $this->selects[] = $fields;

        return $this;
    }

    public function from($from)
    {
        $this->from = $from;

        return $this;
    }

    public function where($column, $value = null, $operator = '=')
    {
        if ($column instanceof \Closure) {
            $this->whereOpen();
            $column($this);
            $this->whereClose();

            return $this;
        }

        $value = $this->checkInOrNotInOperator($operator, $value);

        $this->andWhere[] = sprintf(' %s %s %s ', $column, $operator, $value);

        return $this;
    }

    public function orWhere($column, $value = null, $operator = '=')
    {
        if ($column instanceof \Closure) {
            $this->whereOpen('or');
            $column($this);
            $this->whereClose('or');

            return $this;
        }

        $value = $this->checkInOrNotInOperator($operator, $value);

        $this->orWhere[] = sprintf(' %s %s %s ', $column, $operator, $value);

        return $this;
    }

    public function orderBy($column, $dir = 'ASC')
    {
        $this->orderBy[] = "{$column} {$dir}";

        return $this;
    }

    public function getQuery()
    {
        $queryStatement = sprintf('SELECT %s FROM %s',
            implode(', ', $this->selects),
            $this->from
        );

        if (count($this->andWhere)) {
            $queryStatement .= implode(' AND ', $this->andWhere);
        }

        if (count($this->orWhere)) {
            $queryStatement .= implode(' OR ', $this->orWhere);
        }

        if (count($this->orderBy)) {
            $queryStatement .= sprintf(' ORDER BY %s', implode(', ', $this->orderBy));
        }

        return $this->queryBeautifier($queryStatement);
    }

    public function newQuery()
    {
        $this->initialQueryBuilder();

        return $this;
    }

    private function whereOpen($whereType = 'and')
    {
        $this->{strtolower($whereType) . 'Where'}[] = '(';
    }

    private function whereClose($whereType = 'and')
    {
        $this->{strtolower($whereType) . 'Where'}[] = ')';
    }

    private function checkInOrNotInOperator($operator, $value)
    {
        if (strtoupper($operator) === 'IN' || strtoupper($operator) === 'NOT IN') {
            $value = sprintf(" ('%s') ", implode("', '", $value));
        } else {
            $value = "'{$value}'";
        }

        return $value;
    }

    private function queryBeautifier($query)
    {
        $findWhatArr = array(
            '/\(\s?and\s?/i',
            '/\s?and\s?\)/i',
            '/\s{2,}/',
        );
        $replaceWithArr = array(
            '(',
            ')',
            ' ',
        );

        return preg_replace($findWhatArr, $replaceWithArr, $query);
    }

    private function initialQueryBuilder()
    {
        $this->selects = [];
        $this->from = '';
        $this->andWhere = [];
        $this->orWhere = [];
        $this->orderBy = [];
    }
}
