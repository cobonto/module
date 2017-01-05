<?php

namespace Module\Classes;


class Middleware
{

    /**
     * @var Module $module
     */
    protected $module;
    public function __construct($module)
    {
        $this->module = $module;
    }
    // add middleware to middleware.php in cache
    public function addMiddleware()
    {
        if($moduleMiddleware = $this->module->middleware())
        {
            $middleware = $this->loadMiddleware();
            $middleware = array_merge($middleware,$moduleMiddleware);
            return $this->setMiddleware($middleware);
        }
        return true;
    }
    // remove middleware to middleware.php in cache
    public function removeMiddleware()
    {
        if($moduleMiddleware = $this->module->middleware())
        {
            $middleware = $this->loadMiddleware();
            $middleware = array_diff($middleware,$moduleMiddleware);
            return $this->setMiddleware($middleware);
        }
        return true;
    }
    public function loadMiddleware()
    {
        return app('files')->getRequire($this->getMiddlewareCachedPath());
    }
    protected function getMiddlewareCachedPath()
    {
        return app('path.bootstrap').DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'middleware.php';
    }
    protected function setMiddleware($data)
    {
        return app('files')->put($this->getMiddlewareCachedPath(),'<?php'.PHP_EOL.'return '.var_export($data,true).';'.PHP_EOL);
    }
}