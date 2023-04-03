<?php

namespace SpeedFramework\Core\ExceptionHandling;


class System
{
    public function setExceptionHandler(callable $handler)
    {
        return set_exception_handler($handler);
    }

    public function startOutputBuffering()
    {
        return ob_start();
    }

    public function cleanOutputBuffer()
    {
        return ob_get_clean();
    }

    public function getOutputBufferLevel()
    {
        return ob_get_level();
    }

    public function endOutputBuffering()
    {
        return ob_end_clean();
    }

    public function flushOutputBuffer()
    {
        flush();
    }
}