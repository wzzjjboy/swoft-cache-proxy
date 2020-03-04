<?php declare(strict_types=1);

namespace alan\swoft_cache_proxy\Annotation\Parser;

use alan\swoft_cache_proxy\Annotation\Mapping\Cache;
use alan\swoft_cache_proxy\CacheRegister;
use alan\swoft_cache_proxy\Exception\CacheException;
use Swoft\Annotation\Annotation\Mapping\AnnotationParser;
use Swoft\Annotation\Annotation\Parser\Parser;


/**
 * Class AfterParser
 *
 * @AnnotationParser(Cache::class)
 *
 * @since 2.0
 */
class CacheParser extends Parser
{
    /**
     * Parse `After` annotation
     *
     * @param int   $type
     * @param CacheProxy $annotationObject
     *
     * @return array
     * @throws CacheException
     */
    public function parse(int $type, $annotationObject): array
    {
        if ($type !== self::TYPE_METHOD) {
            throw new CacheException('`@Cache` must be defined by method!');
        }
        
        CacheRegister::register($this->className, $this->methodName, $annotationObject->getIsQuery());

        return [];
    }
}
