<?php

namespace SpeedFramework\Core;

use app\setup\SetupApp;
use app\speedframework\src\controllers\ErrorController;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

class Application extends HttpKernel
{
    private $matcher;
    private $controllerResolver;
    private $argumentResolver;
    protected $dispatcher;


    public function __construct(SetupApp $setupApp)
    {
        $container = $setupApp->getServices();
        $dispatcher = $setupApp->getDispatcher();
        $routes = $setupApp->getRoutes();

//        $routes = include __DIR__.'../../core/config.php';

        $context = new RequestContext();
        $this->matcher = new UrlMatcher($routes, $context);
        $requestStack = new RequestStack();

        $this->controllerResolver = new ControllerResolver();
        $this->argumentResolver = new ArgumentResolver();

        $this->dispatcher = new EventDispatcher();
        $errorListener = new ErrorListener(ErrorController::class."::exception");
        $this->dispatcher->addSubscriber($errorListener);

        parent::__construct($this->dispatcher, $this->controllerResolver, $requestStack, $this->argumentResolver, true);
    }

    public function handle(
        Request $request,
        $type = HttpKernelInterface::MAIN_REQUEST,
        $catch = true
    ) : Response
    {
        $this->matcher->getContext()->fromRequest($request);

        try {
            $request->attributes->add($this->matcher->match($request->getPathInfo()));

            $controller = $this->controllerResolver->getController($request);
            $arguments = $this->argumentResolver->getArguments($request, $controller);

            $response = call_user_func_array($controller, $arguments);
        } catch (ResourceNotFoundException $exception) {
            $response = new Response('Not Found', 404);
        } catch (\Exception $exception) {
            $response = new Response('An error occurred', 500);
        }

        $this->dispatcher->dispatch(new ResponseEvent($this, $request, $type, $response));

        return $response;
    }

}