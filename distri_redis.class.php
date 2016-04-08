<?php
/**
 * Created by PhpStorm.
 * User: zhangshuang
 * Date: 15/11/3
 * Time: 17:42
 */
include_once "config.class.php";
include_once "pip_redis.class.php";

class DRedis
{

    /**
     * @var object instance of DRedis
     */
    private static $_instance;

    /**
     * @var array|null distributed redis clients
     */
    private $redis_clients;

    /**
     * @var int the number of distributed redis clients
     */
    private $redis_client_num;


    /**
     * constructor of distri_redis, declare private, can't be Instanced
     */
    private function __construct()
    {
        if (!empty(DistriRedisConfig::$REDIS_SERVERS)) {
            $this->redis_client_num = count(DistriRedisConfig::$REDIS_SERVERS);
            foreach (DistriRedisConfig::$REDIS_SERVERS as $server_config) {
                $client_host = isset($server_config['host']) ? trim($server_config['host']) : '';
                $client_port = isset($server_config['port']) ? intval($server_config['port']) : 6379;
                $client_db = isset($server_config['db']) ? intval($server_config['db']) : 0;
                $client_passwd = isset($server_config['passwd']) ? trim($server_config['passwd']) : '';
                $timeout = isset($server_config['timeout']) ? intval($server_config['timeout']) : 1;
                $client = $this->connect($client_host, $client_port, $client_db, $client_passwd, $timeout);

                if (!empty($client)) {
                    $this->redis_clients[] = $client;
                }
            }
        }
    }

    /**
     * connect to redis server
     * @param string $host host of redis server
     * @param int $port port of redis server
     * @param int $db int db of redis server
     * @param string $passwd string password of redis server
     * @param int $timeout timeout when connecting to redis server
     * @return bool|Redis redis instance
     */
    private function connect($host, $port, $db = 0, $passwd = '', $timeout = 1)
    {
        try {
            $client = new Redis();

            if ($client->connect($host, $port, $timeout)) {
                if (!empty($passwd)) {
                    $client->auth($passwd);
                }

                if (!empty($db)) {
                    $client->select($db);
                }
                return $client;
            } else {
                trigger_error("redis server went array!$host:$port-$db-$passwd", E_USER_ERROR);
                return false;
            }
        } catch (Exception $e) {
            trigger_error("redis server went array!$host:$port-$db-$passwd", E_USER_ERROR);
            return false;
        }

    }


    /**
     * forbid to clone the object
     */
    public function __clone()
    {
        trigger_error("clone is not allow!", E_USER_ERROR);
    }

    /**
     * @return DRedis|object get the instance of DRedis
     */
    public static function getInstance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @param $key string|int redis key to calculate the node of redis clients;
     * @return bool|Redis a certain redis instance base on the key
     */
    public function getRedisInstance($key)
    {
        $node = $this->getRedisNode($key);
        if ($node !== false) {
            return isset($this->redis_clients[$node]) ? $this->redis_clients[$node] : false;
        }
        return false;
    }

    /**
     * @return array|null get all redis clients
     */
    public function getRedisClients()
    {
        return $this->redis_clients;
    }

    /**
     * @return int get the number of redis clients
     */
    public function getRedisClientsNum()
    {
        return $this->redis_client_num;
    }

    /**
     * @param $key string|int redis key to calculate the node of redis clients;
     * @return bool|mixed  Redis a certain node of redis clients base on the key
     */
    public function getRedisNode($key)
    {

        if (empty($this->redis_clients)) {

            return false;
        }

        if (is_array($this->redis_clients)) {
            $nodes = array_keys($this->redis_clients);

            $consitent = new ConsistentHashAlgo();
            $consitent->addNodes($nodes);
            $node = $consitent->getNode($key);

            return $node;
        } else {
            return false;
        }
    }

    public function mGet(Array $keys, $sort = false)
    {

        if (empty($this->redis_clients)) {
            return false;
        }

        $key_distri_map = array();

        foreach ($keys as $key) {
            $node = $this->getRedisNode($key);

            if ($node === false) {
                continue;
            }

            $key_distri_map[$node][] = $key;
        }

        $kv = array();

        foreach ($key_distri_map as $node => $node_keys) {
            if (!empty($this->redis_clients[$node])) {
                $redis_client = $this->redis_clients[$node];
                $pip_redis_client = new PipRedis($redis_client);
                $node_kv = $pip_redis_client->pipMGet($node_keys);
                if (!empty($node_kv) && is_array($node_kv)) {
                    $kv = array_merge($kv, $node_kv);
                }
            }

        }

        if ($sort) {

            $sorted_kv = array();

            foreach ($keys as $key) {
                $sorted_kv[$key] = isset($kv[$key]) ? $kv[$key] : false;
            }

            $kv = $sorted_kv;
        }

        return $kv;

    }

    /**
     * @param array $key_fields format
     *Array
     *  (
     *      [k_0] => Array
     *      (
     *          [0] => f17
     *          [1] => f19
     *      )
     *
     *      [k_1] => Array
     *      (
     *          [0] => f17
     *          [1] => f18
     *      )
     *
     *      ....
     *
     *      [{key}]=>Array
     *      (
     *           [0] => {field1}
     *           [0] => {field2}
     *      )
     *
     *  )
     *
     *
     * @return array|bool format
     * Array
     *      (
     *          [k_0] => Array
     *          (
     *              [f17] => k_0~f17
     *              [f19] => k_0~f19
     *          )
     *
     *          [k_1] => Array
     *          (
     *              [f17] => k_1~f17
     *              [f18] => k_1~f18
     *          )
     *
     *          ......
     *
     *          [{key}] => Array
     *          (
     *               [{field1}] => [{value1}]
     *               [{field2}] => [{value2}]
     *          )
     *
     *      )
     */
    public function hMGetDistriByField(Array $key_fields)
    {
        $node_key_field = array();

        $field_node = array();

        foreach ($key_fields as $key => $fields) {
            foreach ($fields as $field) {
                if (isset($field_node[$field])) {
                    $node = $field_node[$field];
                } else {
                    $node = $this->getRedisNode($field);
                    $field_node[$field] = $node;
                }

                if ($node !== false) {
                    $node_key_field[$node][$key][] = $field;
                }
            }
        }

        if (!empty($node_key_field)) {
            $redis_clients = DRedis::getInstance()->getRedisClients();

            $result = array();

            if (is_array($node_key_field)) {
                foreach ($node_key_field as $_node => $_key_field) {
                    if (!empty($redis_clients[$_node])) {
                        $redis_client = $redis_clients[$_node];
                        $pip_redis_client = new PipRedis($redis_client);
                        $key_fields_values = $pip_redis_client->pipHMGet($_key_field);

                        if (is_array($key_fields_values)) {
                            foreach ($key_fields_values as $key => $field_value) {
                                if (empty($result[$key])) {
                                    $result[$key] = array();
                                }
                                if (!empty($field_value)) {
                                    $result[$key] = $result[$key] + $field_value;
                                }
                            }
                        }

                    }
                }
            }
            return $result;
        } else {
            return false;
        }
    }


    /**
     * @param array $key_fields format
     *Array
     *  (
     *      [k_0] => Array
     *      (
     *          [0] => f17
     *          [1] => f19
     *      )
     *
     *      [k_1] => Array
     *      (
     *          [0] => f17
     *          [1] => f18
     *      )
     *
     *      ....
     *
     *      [{key}]=>Array
     *      (
     *           [0] => {field1}
     *           [0] => {field2}
     *      )
     *
     *  )
     *
     *
     * @return array|bool format
     * Array
     *      (
     *          [k_0] => Array
     *          (
     *              [f17] => k_0~f17
     *              [f19] => k_0~f19
     *          )
     *
     *          [k_1] => Array
     *          (
     *              [f17] => k_1~f17
     *              [f18] => k_1~f18
     *          )
     *
     *          ......
     *
     *          [{key}] => Array
     *          (
     *               [{field1}] => [{value1}]
     *               [{field2}] => [{value2}]
     *          )
     *
     *      )
     */
    public function hMGet(Array $key_fields)
    {

    }


}

/**
 * Consistent hashing algorithm
 * Class ConsistentHashAlgo
 */
class ConsistentHashAlgo
{


    /**
     * vertual node
     * @var int
     */
    private $virtualNode;

    /**
     * hash algorithm callable
     * @var string callable
     */
    private $hashing;


    /**
     * all nodes
     * @var array
     */
    private $nodes;

    /**
     * virtualNodes
     * @var array
     */
    private $virtualNodes;

    /**
     * is sort
     * @var boolean
     */
    private $isSort = false;

    private $rltVirtualNodes = array();

    /**
     * construct
     * @param integer $virtualNode
     * @param callable|string $hashing
     */
    public function __construct($virtualNode = 64, $hashing = 'crc32')
    {
        $this->virtualNode = $virtualNode;
        $this->hashing = $hashing;
    }

    /**
     * add single node
     * @param string $node
     * @return $this
     */
    public function addNode($node)
    {
        if (isset($this->nodes[$node])) {
            return $this;
        }
        $this->rltVirtualNodes[$node] = array();
        for ($i = 0; $i < $this->virtualNode; $i++) {
            $hash = call_user_func($this->hashing, $node . $i);
            $this->virtualNodes[$hash] = $node;
            $this->rltVirtualNodes[$node][] = $hash;
        }
        $this->isSort = false;
        return $this;
    }

    /**
     * add  several nodes
     * @param array $nodes
     * @return $this
     */
    public function addNodes(array $nodes)
    {
        foreach ($nodes as $node) {
            $this->addNode($node);
        }
        return $this;
    }

    /**
     * get node base on the key
     * @param $key
     * @return mixed
     * @throws Exception
     */
    public function getNode($key)
    {
        if (empty($this->virtualNodes)) {
            return false;
        }
        if (!$this->isSort) {
            ksort($this->virtualNodes);
            $this->isSort = true;
        }

        $hash = call_user_func($this->hashing, $key);

        foreach ($this->virtualNodes as $vritualNode => $node) {
            if ($vritualNode > $hash) {
                return $node;
            }
        }

        reset($this->virtualNodes);

        return current($this->virtualNodes);
    }
}