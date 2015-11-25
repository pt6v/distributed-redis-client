<?php
/**
 * Created by PhpStorm.
 * User: zhangshuang
 * Date: 15/11/4
 * Time: 14:35
 */
//require "distri_redis.class.php";
//
//
////$c = DRedis::getInstance()->getRedisClients();
////$c = DRedis::getInstance()->getRedisNode('c');
//$c = DRedis::getInstance()->getRedisInstance('c');
//var_dump($c);
//
//for($i = 0; $i<10; $i++) {
//    $key = "XXXX" . $i;
//    $node = DRedis::getInstance()->getRedisInstance("XXXX" . $i);
//    $node->setex($key,1800, "is number $i");
//}
// php test.php


////$ids_str = "1,2,3,4,5,6";
//
//$ids_str = $_GET['ids'];
//$ids = explode(',' ,$ids_str);
////$ids_new = shuffle($ids);
//shuffle($ids);
//
//echo implode(",", $ids);

//echo DRedis::getInstance()->getRedisNode('like');
require "pipe_redis.class.php";

$redis_client = new Redis();
$redis_client->connect("127.0.0.1", 6379, 1);

$redis_client->select(1);
$pip_redis = new PipeRedis($redis_client);
//$a = $pip_redis->pipGet(array(
//    'a1',
//    'a2',
//    'a3',
//    'a4',
//));
$a = $pip_redis->pipHGet(array('k1'=>array('f1','f2', 'f3'),'k2'=>array('f1','f2','f3')));
var_dump($a);