<?php
/**
 * Created by PhpStorm.
 * User: fara
 * Date: 2/8/2017
 * Time: 12:19 AM
 */

namespace Module\Classes\Actions;


use Module\Classes\Module;

abstract class Action implements ActionContract
{
    /** @var Module */
    protected $module;
    /** @var string  */
    protected $file;
    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function load()
    {
        return app('files')->getRequire($this->get());
    }
    protected function get()
    {
        return app('path.bootstrap').DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.$this->file.'.php';
    }
    protected function set($data)
    {
        return app('files')->put($this->get(),'<?php'.PHP_EOL.'return '.var_export($data,true).';'.PHP_EOL);
    }
}