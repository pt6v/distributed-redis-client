<?php
/**
 * Created by PhpStorm.
 * User: zhangshuang
 * Date: 15/11/4
 * Time: 14:35
 */
require "distri_redis.class.php";


//$c = DRedis::getInstance()->getRedisClients();
//$c = DRedis::getInstance()->getRedisNode('c');
$c = DRedis::getInstance()->getRedisInstance('c');
var_dump($c);

for($i = 0; $i<10; $i++) {
    $key = "XXXX" . $i;
    $node = DRedis::getInstance()->getRedisInstance("XXXX" . $i);
    $node->setex($key,1800, "is number $i");
}