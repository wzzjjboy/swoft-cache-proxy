<?php


namespace alan\swoft_cache_proxy;

use Swoft\Log\Helper\CLog;
use Swoft\Log\Helper\Log;
use Swoft\Redis\Redis;
use Swoft\Stdlib\Helper\JsonHelper;

class Cache
{
    const CACHE_FIELD = "__cache_field";

    public $class;

    public $method;

    public $isQueryNode;

    public $cacheField = self::CACHE_FIELD;

    public $clearCacheField = true;

    public static $KeyForCondition = 'set_keys_' . self::CACHE_FIELD;

    public static $showLog = true;

    public function isQueryNode()
    {
        return boolval($this->isQueryNode);
    }

    public function getFromCache(array $condition)
    {
        if (empty($firstKey = $this->getQueryKey($condition))){
            return [];
        }
        $key = $this->getKey($firstKey);
        $hKey = $this->getHashKey($condition);
        $cacheRsp = Redis::hget($key, $hKey);
        if (empty($cacheRsp)){
            $rsp = [];
        } else {
            $rsp = JsonHelper::decode($cacheRsp, true);
        }
        self::log(__METHOD__, "redis::hget({$key}", $hKey, " rsp", $rsp);
        
        return $rsp;
    }

    public function setCache(array &$getData, array $condition)
    {
        list($key, $hashKey) =  $this->getQueryKeyInner($condition);
        if (empty($mapVal = $this->getCacheVal($getData, true))){
            self::log(__METHOD__, "Redis getCacheVal is empty");
            return;
        }
        $rsp = Redis::hSet($key, $hashKey, $mapVal); //class+method__condition_prefix+condition => cacheField
        self::log(__METHOD__, "Redis::hSet(", $key, $hashKey, $mapVal, " rsp", $rsp);
        $val = JsonHelper::encode($getData, JSON_UNESCAPED_UNICODE);
//        print_r([$getData, $condition, $mapVal]);
        $key = $this->getKey($mapVal);
        $hashKey = $this->getHashKey($condition);
        $rsp = Redis::hSet($key, $hashKey, $val); //class+cacheField__method+condition => data
        self::log(__METHOD__, "Redis::hSet(", $key, $hashKey, $val, " rsp", $rsp);

        $this->pushCondition($mapVal, $condition); //key+cacheField => condition
    }

    public function getClearCacheKeys($val){
        $keys = [];
        if (!$this->isQueryNode()){
            return $keys;
        }
        $v = [self::CACHE_FIELD => $val];
        if ($key = $this->getCacheVal($v)){
            $k = $this->getKey($key); //class+cacheField => key
            $keys[] = $k;
        }
        return $keys;
    }

    private static function cacheDel(...$args){
        $rsp = Redis::del(...$args);
        self::log(__METHOD__, "redis::del(", $args, ") rsp", $rsp);
    }

    private static function cacheHashKeyDel($keys){
        foreach ($keys as $key => $hKey){
            if (!is_array($hKey)){
                $hKey = [$hKey];
            }
            $rsp = Redis::hDel($key, ...$hKey);
            self::log(__METHOD__, "redis::hdel({$key}", json_encode($hKey), ") rsp", $rsp);
        }
    }

    /**
     * @param Cache[] $nodes
     * @param $val
     */
    public static function batchClearCache(array $nodes, $val) {
       if (!is_array($val)){
           $val = [$val];
       }

       $hKey = $sKey = [];
       foreach ($val as $cacheFlagVal) {
           $allCondition = self::popCondition($cacheFlagVal);
           $keys = [];
           foreach ($nodes as $node) {
               $keys = array_merge($keys, $node->getClearCacheKeys($cacheFlagVal));
               foreach ($allCondition as $conditionItem) {
                   if ($node->isQueryNode()){
                       list($_key, $_val) =  $node->getQueryKeyInner($conditionItem); //class+method__getFirstKey+condition => [key + hKey]...
                       $keys[$_key] = $_val;
                   }
               }
           }

           if (!empty($keys)){
               $keys = array_unique($keys);
               foreach ($keys as $i => $key) {
                   if (is_string($i)) {
                       $hKey[$i] = $key;
                   } else {
                    $sKey[] = $key;
                   }
               }
           }

           !empty($hKey) && self::cacheHashKeyDel($hKey);
           if ($conditionKey = self::getKeyForCondition($cacheFlagVal)) {
               $sKey[] = $conditionKey;
           }
           if ($sKey) {
               self::cacheDel(...$sKey);
           }
       }
    }


    private function format(string $string): string {
        return str_replace([
            "\\",
            "\"",
            "{",
            "}",
            ":",
            ",",
            "-",
        ], "_", $string);
    }

    private function getKey($key):string
    {
        $rsp = sprintf("%s_%s", strtolower($this->class), strtolower($key));
        $rsp = $this->format($rsp);
        return $rsp;
    }

    private function getHashKey(array $condition):string
    {
        $rsp = sprintf("%s-%s", strtolower($this->method), self::conditionToStr($condition));
        $rsp = $this->format($rsp);
        return $rsp;
    }

    private function getQueryKey(array $condition)
    {
        list($key, $hashKey) =  $this->getQueryKeyInner($condition);
        $rsp = Redis::hGet($key, $hashKey);
        self::log(__METHOD__, " Redis::hGet(", $key, $hashKey,")", "rsp", $rsp);
        return $rsp;
    }

    private function getQueryKeyInner($condition){
        $rsp = [
            $this->format(sprintf("%s_%s", strtolower($this->class), strtolower($this->method))),
            $this->format(sprintf("%s_%s", 'condition_prefix', self::conditionToStr($condition))),
        ];
        return $rsp;
    }

    private static function conditionToStr(array $condition)
    {
        return JsonHelper::encode($condition);
    }

    private static function conditionToArr(string $condition)
    {
        return JsonHelper::decode($condition, true);
    }

    public function getCacheVal(array &$data, $clear = false)
    {
        $val = $data[$this->cacheField] ?? false;
        if ($clear && $val !== false){
            $data = self::removeCacheField($data, $this);
        }
        return $val;
    }

    public static function removeCacheField($data, $node)
    {
        unset($data[$node->cacheField]);
        return $data;
    }

    private static function log($method, ...$args) {
        self::$showLog && CLog::info(sprintf("[cache_log]cache method:%s args: %s" . PHP_EOL, $method, JsonHelper::encode($args, JSON_UNESCAPED_UNICODE)));
    }

    private function pushCondition($cacheFlag, array $condition)
    {
        $key = self::getKeyForCondition($cacheFlag);
        $val = self::conditionToStr($condition);
        $rsp = Redis::sAdd($key, $val);
        self::log(__METHOD__, " Redis::sAdd(", $key, $val,")", "rsp", $rsp);
    }

    /**
     * @param $mapVal
     * @return  array
     */
    private static function popCondition($mapVal)
    {
        $key = self::getKeyForCondition($mapVal);
        $allCondition = Redis::sMembers($key);
        self::log(__METHOD__, " Redis::sMembers(", $key,")", "rsp", $allCondition);
        $condition = [];
        foreach ($allCondition as $conditionStr) {
            $condition[] = self::conditionToArr($conditionStr);
        }
        return $condition;
    }

    private static function getKeyForCondition($cacheFlag)
    {
        $key = self::$KeyForCondition;
        return sprintf("{$key}_%s",  strval($cacheFlag));
    }
}