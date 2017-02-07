<?php

namespace Module\Classes\Actions;


class Middleware extends Action
{
    protected $file ='middleware';
    // add middleware to middleware.php in cache
    public function add()
    {
        if($moduleMiddleware = $this->module->middleware())
        {
            $middleware = $this->load();
            $middleware = array_merge($middleware,$moduleMiddleware);
            return $this->set($middleware);
        }
        return true;
    }
    // remove middleware to middleware.php in cache
    public function remove()
    {
        if($moduleMiddleware = $this->module->middleware())
        {
            $middleware = $this->load();
            $middleware = array_diff($middleware,$moduleMiddleware);
            return $this->set($middleware);
        }
        return true;
    }
}