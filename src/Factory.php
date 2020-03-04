<?php


namespace alan\swoft_cache_proxy;

use alan\swoft_cache_proxy\Exception\NotDefinedNodeException;

class Factory
{
    private static $container = [];

    /**
     * @param string $class
     * @param string $method
     * @return Cache
     */
    public static function getBean(string $class, string $method): Cache
    {
        return self::getBeanInner($class, $method);
    }

    /**
     * @param string $class
     * @return Cache[]
     */
    public static function getAffectedBean(string $class): array
    {
        $container = [];
        $all = CacheRegister::getNode($class, true);
        foreach ($all as  $item) {
            list($class, $method) = $item;
            $container[] = self::getBeanInner($class, $method);
        }
        return $container;
    }

    /**
     * @param string $class
     * @param string $method
     * @return Cache
     * @throws NotDefinedNodeException
     */
    private static function getBeanInner(string $class, string $method)
    {
        $key = sprintf("%s::%s", $class, $method);

        if (isset(self::$container[$key])){
            return self::$container[$key];
        }
        $nodeType = CacheRegister::getNodeType($class, $method);
        if (null === $nodeType) {
            throw new NotDefinedNodeException(sprintf("not defined not class:%s method:%s", $class, $method));
        }

        $node = new Cache();
        $node->class = $class;
        $node->method = $method;
        $node->isQueryNode = $nodeType;
        self::$container[$key] = $node;

        return $node;
    }
}