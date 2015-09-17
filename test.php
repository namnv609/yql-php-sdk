<?php

ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

use NNV\YQL\YQL;
use NNV\YQL\QueryBuilder;

$q = new QueryBuilder();
$yql = new YQL();

function dd($var)
{
   echo "<pre>";
   var_dump($var);
   echo "</pre>";
}

$sql = $q->select('*')
         ->from('html')
         ->where('url', 'http://m.nhaccuatui.com/tim-kiem/bai-hat?q=anh%20that%20ngoc')
         ->where('xpath', '//div[contains(@class, "bgmusic")]/h3/a');

$a = $yql->execute($sql);
// echo($sql->getQuery());
$obj = json_decode($a);

dump($obj);

// echo $sql;
