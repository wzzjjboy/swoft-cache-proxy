# swoft-cache-proxy

1. #### 特性

   - 基于框架本身AOP和Annotation实现

   - 接入成本较低

     

2. #### 安装

    composer require alan/swoft-cache-proxy

   

3. #### 配置redis

   ```php
   'redis'               => [
       'class'         => RedisDb::class,
       'host'          => env("REDIS_HOST"),
       'port'          => env("REDIS_PORT"),
       'database'      => env("REDIS_DATABASE"),
       'retryInterval' => 10,
       'readTimeout'   => 0,
       'timeout'       => 2,
       'password'      => env("REDIS_PASSWORD"),
       'driver'        => 'phpredis',
       'option'        => [
           'prefix' => 'swoft_app_name',
           'serializer' => Redis::SERIALIZER_NONE,
       ],
   ],
   'redis.pool'          => [
       'class'       => \Swoft\Redis\Pool::class,
       'redisDb'     => \bean('redis'),
       'minActive'   => 10,
       'maxActive'   => 20,
       'maxWait'     => 0,
       'maxWaitTime' => 0,
       'maxIdleTime' => 60,
   ]
   ```

   

4. #### 在业务上层代码引入Cache注解

   ```php
   use alan\swoft_cache_proxy\Annotation\Mapping\Cache;
   
   /**
    * @Cache(isQuery=true)
    * @return array
    */
   public function testQuery() {
       $cacheFlagValue = 123;
       $now = time(); //业务代码
       return CacheRspHelper::makeRsp(['now' => $now], $cacheFlagValue);
   }
   
   /**
    * @Cache(isQuery=false)
    * @return array
    */
   public function testUpdate() {
       $cacheFlagValue = 123;
       return CacheRspHelper::makeRsp(['now' => time()], $cacheFlagValue);
   }
   ```

   - 引入Cache注释，注意要引入命令空间
   - Cache注释有个isQuery的属性，如果当前的操作是查询则设置为true,如果当前是更新操作则为false.比如前当我们是从数据库里面查出某个用户的订单，这里会把查询结果缓存起来，如果是更新某个用户订单则会把之前查询的缓存清空。
   - 如果isQuery=ture时，返回数据时需要使用CacheRspHelper::makeRsp函数返回。第一个参数为业务层返回的数据（即需要缓存的数据）、第二个参数为缓存数据的归属者标识字段，如何上面我们查询的是某用户(uid=10000)的订单列表数据，那这里应该传入该用户的uid(即10000).

