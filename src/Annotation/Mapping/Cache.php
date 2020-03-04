<?php declare(strict_types=1);

namespace alan\swoft_cache_proxy\Annotation\Mapping;

use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class CacheProxy
 *
 * @Annotation
 * @Target("METHOD")
 * @Attributes({
 *      @Attribute("isQuery", type="bool")
 * })
 *
 * @since 2.0
 */
final class Cache
{
    /**
     * @var string
     */
    private $value = '';

    /**
     * @var bool
     */
    private $isQuery = true;

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value)
    {
        $this->value = $value;
    }
    
    /**
     * @return string
     */
    public function getIsQuery(): bool 
    {
        return $this->isQuery;
    }

    /**
     * @param string $value
     */
    public function setIsQuery(bool $value)
    {
        $this->isQuery = $value;
    }

    public function __construct($values)
    {
        if (isset($values['value'])) {
            $this->value = $values['value'];
        }
        if (isset($values['isQuery'])) {
            $this->isQuery = $values['isQuery'];
        }
    }
}