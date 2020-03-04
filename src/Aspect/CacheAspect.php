<?php

namespace alan\swoft_cache_proxy\Aspect;

use Throwable;
use Swoft\Aop\Annotation\Mapping\Around;
use alan\swoft_cache_proxy\Factory;
use Swoft\Aop\Annotation\Mapping\Aspect;
use Swoft\Aop\Point\ProceedingJoinPoint;
use Swoft\Aop\Annotation\Mapping\PointBean;
use Swoft\Aop\Annotation\Mapping\PointAnnotation;
use alan\swoft_cache_proxy\Annotation\Mapping\Cache;
use alan\swoft_cache_proxy\Cache as CacheNode;

/**
 * Class CacheAspect
 * @Aspect(order=1)
 *
 * @PointAnnotation(
 *     include={Cache::class}
 * )
 *
 */
class CacheAspect
{

//    /**
//     * 前置通知
//     *
//     * @Before()
//     */
//    public function beforeAdvice()
//    {
//        var_dump(__METHOD__);
//    }
//
//    /**
//     * 后置通知
//     *
//     * @After()
//     */
//    public function afterAdvice()
//    {
//        var_dump(__METHOD__);
//    }
//
//    /**
//     * 返回通知
//     *
//     * @AfterReturning()
//     *
//     * @param JoinPoint $joinPoint
//     *
//     * @return mixed
//     */
//    public function afterReturnAdvice(JoinPoint $joinPoint)
//    {
//        var_dump(__METHOD__);
//        $ret = $joinPoint->getReturn();
//        // 返回
//        return $ret;
//    }
//
//    /**
//     * 异常通知
//     *
//     * @AfterThrowing()
//     *
//     * @param Throwable $throwable
//     */
//    public function afterThrowingAdvice(Throwable $throwable)
//    {
//        var_dump(__METHOD__);
//    }

    /**
     * 环绕通知
     *
     * @Around()
     *
     * @param ProceedingJoinPoint $proceedingJoinPoint
     *
     * @return mixed
     * @throws Throwable
     */
    public function aroundAdvice(ProceedingJoinPoint $proceedingJoinPoint)
    {
//        print_r(sprintf("cache class:%s method:%s\n", $proceedingJoinPoint->getClassName(), $proceedingJoinPoint->getMethod()));
        $class = $proceedingJoinPoint->getClassName();
        $method = $proceedingJoinPoint->getMethod();
        $condition = $proceedingJoinPoint->getArgsMap();
        /** @var \alan\swoft_cache_proxy\Cache $bean */
        $bean = Factory::getBean($class, $method);

        if ($bean->isQueryNode() && $cacheData = $bean->getFromCache($condition)) {
            return $cacheData;
        }
        // 前置通知
        $rsp = $proceedingJoinPoint->proceed();
        if ($bean->isQueryNode()) {//限制1.查询数据时返回字段必须包括cacheField
            $bean->setCache($rsp, $condition);
        } else {////限制2.更新数据时返回字段必须包括cacheField
            $cacheFieldVal = false;
            $nodes = Factory::getAffectedBean($class);
            if (isset($nodes[0])) {
                $cacheFieldVal = $nodes[0]->getCacheVal($rsp);
            }
            $cacheFieldVal && CacheNode::batchClearCache($nodes, $cacheFieldVal);
            $rsp = CacheNode::removeCacheField($rsp, $nodes[0]);
        }
        // 后置通知
        return $rsp;
    }
}