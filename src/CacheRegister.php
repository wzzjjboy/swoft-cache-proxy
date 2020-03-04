<?php declare(strict_types=1);


namespace alan\swoft_cache_proxy;


/**
 * Class AspectRegister
 *
 * @since 2.0
 */
class CacheRegister
{
    /**
     * @var array
     */
    private static $cacheNodes = [];

    private static $groupData = [];

    /**
     * Register advice
     *
     * @param string $type
     * @param string $className
     * @param string $methodName
     */
    public static function register(string $className, string $methodName, bool $isQuery): void
    {
//        print_r([__METHOD__, func_get_args()]);
        if (isset(self::$cacheNodes[$className][$methodName])) {
            return;
        }
        self::$cacheNodes[$className][$methodName] = $isQuery;
    }

    /**
     * @return bool true|查询结点 fasle|更新结点
     */
    public static function getNodeType(string $className, string $methodName)
    {
        return self::$cacheNodes[$className][$methodName] ?? null;
    }

    /**
     * 按类型获取node
     * @param string $class
     * @param bool $queryNode
     * @return array
     */
    public static function getNode(string $class, bool $isQueryNode): array
    {
        $key = $isQueryNode == true ? 0 : 1;
        if (empty(self::$groupData)){
            foreach (self::$cacheNodes as $class => $item) {
                foreach ($item as $method => $isQuery) {
                    self::$groupData[$class][$key][] = [$class, $method];
                }
            }
        }
        
        return self::$groupData[$class][$key] ?? [];
    }
}