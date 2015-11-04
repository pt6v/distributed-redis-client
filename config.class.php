<?php

/**
 * Created by PhpStorm.
 * User: zhangshuang
 * Date: 15/11/3
 * Time: 17:43
 */
class DistriRedisConfig
{
    public static $REDIS_SERVERS = array(

        /**
         * redis server 1 config
         */
        array(
            'host' => '127.0.0.1', // server 1 host
            'port' => 6379, // server 1 port
            'passwd' => '', // if auth is essential , you need to specify the password.if not, use false or ''
//            'db'=>0, // not essential, you can do select when get the instance of redis
//            'timeout'=>1, // connect to redis timeout
        ),
        /**
         * redis server 2 config
         */
        array(
            'host' => '127.0.0.1', // server 1 host
            'port' => 6380, // server 1 port
            'passwd' => '', // if auth is essential , you need to specify the password.if not, use false or ''
        ),
        // ... ...
    );


}