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