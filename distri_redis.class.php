<?php
/**
 * Created by PhpStorm.
 * User: zhangshuang
 * Date: 15/11/3
 * Time: 17:42
 */
include_once "config.class.php";

class DRedis
{

    private static $_instance;

    private $redis_clients;

    private $redis_client_num;

    private function __construct()
    {
        if (!empty(DistriRedisConfig::$REDIS_SERVERS)) {
            $this->redis_client_num = count(DistriRedisConfig::$REDIS_SERVERS);
            foreach (DistriRedisConfig::$REDIS_SERVERS as $server_config) {
                $client_host = isset($server_config['host']) ? trim($server_config['host']) : '';
                $client_port = isset($server_config['port']) ? intval($server_config['port']) : 6379;
                $client_db = isset($server_config['db']) ? intval($server_config['db']) : 0;
                $client_passwd = isset($server_config['passwd']) ? trim($server_config['passed']) : '';
                $timeout = isset($server_config['timeout']) ? intval($server_config['timeout']) : 1;
                $client = $this->connect($client_host, $client_port, $client_db, $client_passwd, $timeout);

                if (!empty($client)) {
                    $this->redis_clients[] = $client;
                }
            }
        }
    }

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
                return false;
            }
        } catch (Exception $e) {
            echo "connect redis error";
            return false;
        }

    }


    public function __clone()
    {
        trigger_error("clone is not allow!", E_USER_ERROR);
    }

    public static function getInstance()
    {
        if(empty(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function getRedisInstance($key)
    {
        $node =$this->getRedisNode($key);
        if($node !== false) {
            return isset($this->redis_clients[$node]) ? $this->redis_clients[$node] : false;
        }
        return false;
    }

    public function getRedisClients() {
        return $this->redis_clients;
    }

    public function getRedisNode($key) {

        if (empty($this->redis_clients)) {
            var_dump($this->redis_clients);

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


}

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