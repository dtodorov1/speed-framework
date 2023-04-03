<?php

namespace SpeedFramework\Core\ExceptionHandling;


use Whoops\Handler\Handler;
use Whoops\Inspector\InspectorFactory;
use Whoops\Util\Misc;

class Run
{
    const EXCEPTION_HANDLER = 'handleException';

    private $isRegistered = false;
    private InspectorFactory $inspectorFactory;
    private System $system;
    private $handlerStack = [];
    private $frameFilters = [];


    public function __construct()
    {
        $this->inspectorFactory = new InspectorFactory();
        $this->system = new System();
    }


    public function pushHandler($handler)
    {
        $this->handlerStack[] = $handler;
    }

    public function register()
    {
        if (!$this->isRegistered) {
            //TODO what is exactly the purpose of this?
            class_exists("\\Whoops\\Exception\\ErrorException");
            class_exists("\\Whoops\\Exception\\FrameCollection");
            class_exists("\\Whoops\\Exception\\Frame");
            class_exists("\\Whoops\\Exception\\Inspector");
            class_exists("\\Whoops\\Inspector\\InspectorFactory");

            $this->system->setExceptionHandler([$this, self::EXCEPTION_HANDLER]);

            $this->isRegistered = true;
        }

        return $this;
    }

    public function handleException($exception)
    {
        $inspector = $this->getInspector($exception);

        $this->system->startOutputBuffering();

        $handlerResponse = null;
        $handlerContentType = null;

        try {
            foreach (array_reverse($this->handlerStack) as $handler) {
                $handler->setRun($this);
                $handler->setInspector($inspector);
                $handler->setException($exception);

                // The HandlerInterface does not require an Exception passed to handle()
                // and neither of our bundled handlers use it.
                // However, 3rd party handlers may have already relied on this parameter,
                // and removing it would be possibly breaking for users.
                $handlerResponse = $handler->handle($exception);

                // Collect the content type for possible sending in the headers.
                $handlerContentType = method_exists($handler, 'contentType') ? $handler->contentType() : null;

                if (in_array($handlerResponse, [Handler::LAST_HANDLER, Handler::QUIT])) {
                    // The Handler has handled the exception in some way, and
                    // wishes to quit execution (Handler::QUIT), or skip any
                    // other handlers (Handler::LAST_HANDLER). If $this->allowQuit
                    // is false, Handler::QUIT behaves like Handler::LAST_HANDLER
                    break;
                }
            }

            $willQuit = $handlerResponse == Handler::QUIT;
        } finally {
            $output = $this->system->cleanOutputBuffer();
        }

        if ($willQuit) {
            // Cleanup all other output buffers before sending our output:
            while ($this->system->getOutputBufferLevel() > 0) {
                $this->system->endOutputBuffering();
            }

            // Send any headers if needed:
            if (Misc::canSendHeaders() && $handlerContentType) {
                header("Content-Type: {$handlerContentType}");
            }

            // HHVM fix for https://github.com/facebook/hhvm/issues/4055
            $this->system->flushOutputBuffer();
        }

        $this->writeOutput($output);
    }

    public function writeOutput($output)
    {
        echo $output;
        return $this;
    }

    private function getInspector($exception)
    {
        return $this->inspectorFactory->create($exception);
    }

    public function getFrameFilters()
    {
        return $this->frameFilters;
    }

    public function getHandlers()
    {
        return $this->handlerStack;
    }

}