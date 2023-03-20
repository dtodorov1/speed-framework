<?php

namespace app\speedframework;

use app\speedframework\src\Framework;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\Routing\RequestContext;

$containerBuilder = new ContainerBuilder();
$containerBuilder->register('context', RequestContext::class);
//$containerBuilder->register('matcher', UrlMatcher::class)
//    ->setArguments([include __DIR__.'/config.php', new Reference('context')])
//;

$containerBuilder->register('request_stack', RequestStack::class);
$containerBuilder->register('controller_resolver', ControllerResolver::class);
$containerBuilder->register('argument_resolver', ArgumentResolver::class);


//$containerBuilder->register('listener.router', RouterListener::class)
//    ->setArguments([new Reference('matcher'), new Reference('request_stack')])
//;
//$containerBuilder->register('listener.response', ResponseListener::class)
//    ->setArguments(['UTF-8'])
//;
//$containerBuilder->register('listener.exception', ErrorListener::class)
//    ->setArguments(['Calendar\Controller\ErrorController::exception'])
//;

$containerBuilder->register('dispatcher', EventDispatcher::class)
//    ->addMethodCall('addSubscriber', [new Reference('listener.router')])
//    ->addMethodCall('addSubscriber', [new Reference('listener.response')])
//    ->addMethodCall('addSubscriber', [new Reference('listener.exception')])
;

//$containerBuilder->register('framework', Framework::class)
//    ->setArguments([
//        new Reference('dispatcher'),
//        new Reference('controller_resolver'),
//        new Reference('request_stack'),
//        new Reference('argument_resolver'),
//    ])
//;


$containerBuilder->register('framework', Framework::class)
    ->setArguments([
        include __DIR__.'/config.php'
    ])
;

return $containerBuilder;