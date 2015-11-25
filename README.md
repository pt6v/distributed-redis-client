distributed-redis-client 说明
====================

DRedis使用方法
------------------

声明为`private`，并且重写类的`__clone`函数，形成单例的redis实例，获取实例的方法

    $dredis = DRedis::getInstance()

获取某个`$key`所在的redis节点的Redis连接客户端索引

    DRedis::getInstance()->getRedisNode($key);

获取某个`$key`所在的redis节点的Redis连接客户端实例

    DRedis::getInstance()->getRedisInstance($key);

获取所有的redis连接客户端实例

    DRedis::getInstance()->getRedisClients();

获取所有的redis节点个数

    DRedis::getInstance()->getRedisClientsNum();

PipRedis使用方法
----------------

PipRedis将对redis通过`pipline`方式的调用封装减少总I/O次数

`$redis_client`是`Redis`的一个连接实例，初始化:

    $pip_redis = new PipRedis($redis_client)

获取多个`key`的`value`:

    $values = $pip_redis->pipMGet(array(
        'key1',
        'key2',
        'key3',
        'key4',
    ));

`$values` 格式：


    array(4) {
      ["key1"]=>
      string(3) "value1"
      ["key2"]=>
      string(3) "value2"
      ["key3"]=>
      string(3) "value3"
      ["key4"]=>
      bool(false)
    }

获取多个key多个field的hash值

    $result = $pip_redis->pipHMGet(array('k1'=>array('f1','f2', 'f3'),'k2'=>array('f1','f2','f3')));

`$result`格式:

    array(2) {
      ["k1"]=>
      array(3) {
        ["f1"]=>
        string(3) "v11"
        ["f2"]=>
        string(3) "v12"
        ["f3"]=>
        bool(false)
      }
      ["k2"]=>
      array(3) {
        ["f1"]=>
        string(3) "v21"
        ["f2"]=>
        string(3) "v22"
        ["f3"]=>
        string(3) "v23"
      }
    }







