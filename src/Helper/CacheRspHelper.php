<?php


namespace alan\swoft_cache_proxy\Helper;


use alan\swoft_cache_proxy\Cache;

class CacheRspHelper
{
    /**
     * @param $response
     * @param $cacheValue
     * @param string $cacheField
     * @return array
     */
    public static function makeRsp($response, $cacheValue, $cacheField = Cache::CACHE_FIELD): array {
        return ["rsp" => $response, $cacheField => $cacheValue];
    }

    /**
     * @param array $rsp
     * @return array|mixed
     */
    public static function getRsp(array $rsp) {
        return $rsp['rsp'] ?? [];
    }
}