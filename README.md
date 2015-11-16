distributed-redis-client 说明
====================

使用方法
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







