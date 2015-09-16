<?php

ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

use NNV\YQL\YQL;
use NNV\YQL\QueryBuilder;

$q = new QueryBuilder();

$sql = $q->select(['id', 'name', 'abc'])
         ->from('html')
         ->where('url', 'http://google.com')
         ->where(function($query) {
            return $query->orWhere('xpath', 'html/head/title')
                         ->orWhere('xpath', 'body/article/p');
         })
         ->orWhere('xpath', 'html/head/meta')
         ->orderBy('id')
         ->orderBy('name', 'DESC')
         ->getQuery();

echo $sql;
