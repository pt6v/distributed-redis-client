<?php
/**
 * Created by PhpStorm.
 * User: zhangshuang
 * Date: 15/11/4
 * Time: 14:35
 */
require "distri_redis.class.php";
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
//require "pipe_redis.class.php";
//
//$redis_client = new Redis();
//$redis_client->connect("127.0.0.1", 6379, 1);
//
//$redis_client->select(1);
//$pip_redis = new PipRedis($redis_client);
//$a = $pip_redis->pipMGet(array(
//    'a1',
//    'a2',
//    'a3',
//    'a4',
//));
//$a = $pip_redis->pipHMGet(array('k1'=>array('f1','f2', 'f3'),'k2'=>array('f1','f2','f3')));
//var_dump($a);

//$keys = array();

//$field1 = array(
//    'f17',
//    'f18',
//);
//
//$field2 = array(
//    'f17',
//    'f19',
//);
//
//$map = array();
//for($i = 0; $i< 3; $i++) {
//    $key = "k_" . $i;
//    if($i % 2 === 1) {
//        $field = $field1;
//    } else{
//        $field = $field2;
//    }
////    print_r($field);
//    foreach($field as $_field) {
////        $redis_client = DRedis::getInstance()->getRedisInstance($_field);
////        $value = $key."~".$_field;
////        $redis_client->hSet($key, $_field, $value);
//        $map[$key][] = $_field;
//    }
//}
////print_r($map);
//
//$result = DRedis::getInstance()->hMGetDistriByField($map);
//
//print_r($result);
//for($i= 0 ; $i<30; $i ++) {
//    $key = "f".$i;
//    echo $i."=>" . DRedis::getInstance()->getRedisNode($key) . "\n";
//}

//$dredis = DRedis::getInstance();
//$result = $dredis->mGet($keys);
//
//print_r($result);

$fields = array(
    'view',
    'comm',
    'prai',
    'like',
);

for($i = 1; $i<=380; $i++ ) {
    $key = "cpost_" . $i;
    foreach($fields as $field) {
        $rc = DRedis::getInstance()->getRedisInstance($field);
        if($rc instanceof Redis) {
            $rc->hSet($key, $field, $key."~".$field);
        }
    }
}