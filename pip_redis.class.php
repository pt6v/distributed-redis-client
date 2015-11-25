<?php

/**
 * Created by PhpStorm.
 * User: zhangshuang
 * Date: 15/11/25
 * Time: 10:15
 */
class PipRedis
{

    private $redis_client = null;

    public function __construct($redis_client)
    {
        $this->redis_client = $redis_client;
    }

    public function pipGet(Array $keys)
    {
        if ($this->redis_client instanceof Redis) {

            $keys = array_filter($keys);

            $pip = $this->redis_client->multi(Redis::PIPELINE);

            $ret = array();

            foreach ($keys as $key) {
                $pip->get($key);
            }

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            $ret_arr = $pip->exec();

            foreach ($keys as $k => $key) {
                $ret[$key] = $ret_arr[$k];
            }

            return $ret;

        }
        return false;
    }

    public function pipHMGet(Array $key_field_map)
    {
        if ($this->redis_client instanceof Redis) {

            $pip = $this->redis_client->multi(Redis::PIPELINE);

            foreach ($key_field_map as $key => $fields) {
                $pip->hMGet($key, $fields);
            }

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            $ret_arr = $pip->exec();

            $keys = array_keys($key_field_map);
            $ret = array();
            foreach ($keys as $k => $key) {
                $ret[$key] = $ret_arr[$k];
            }
            return $ret;
        }
        return false;
    }


}