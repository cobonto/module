<?php

namespace Module\Classes;

use Illuminate\Support\Facades\View;
use LaravelArdent\Ardent\Ardent;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Module extends Ardent
{
    //
    protected $table = 'modules';
    public $timestamps = false;
    // array of loaded modules instance to prevent instance again
    protected static $instance = array();
    public static $rules = array(
        'name' => 'required|alpha_num',
        'author' => 'required|alpha_num',
        'version' => 'required|alpha_num',
        'active' => 'required|integer',
    );

    // install method
    public function install($author, $name)
    {
        // install module
        // load json data
        try
        {
            $data = Yaml::parse(file_get_contents(app_path() . '/Modules/' . $author . '/' . $name . '/module.yml'));
        } catch (ParseException $e)
        {
            return false;
        }

        // first check module is installed or not
        if (self::isInstalled($data['author'], $data['name']))
        {
            return false;
        }

        else
        {
            $this->name = $name;
            $this->author = $author;
            $this->version = $data['version'];
            $this->active = 1;
            if (!$this->save())
                return false;
            else
                return true;
        }
    }

    public function unInstall($author, $name)
    {
        return true;
    }

    /**
     * @param array $hooks
     * @return bool
     */
    public function registerHooks(array $hooks)
    {
        $hooks[] = $this->name;
        foreach ($hooks as $hook)
        {
            Hook::register($hook, $this->id);
        }
        return true;
    }

    /**
     * check module is installed
     * @param $author
     * @param $name
     * @return bool
     */
    public static function isInstalled($author, $name)
    {
        return count(self::getFromDb($author, $name)) ? true : false;
    }

    public static function getFromDb($author, $name)
    {
        $data = \DB::table('modules')->where('author', $author)->where('name', $name)->first();
        return $data;
    }

    /**
     * get instance of module object
     * @param $author
     * @param $name
     * @return mixed
     */
    public static function getInstance($author, $name)
    {
        if (!isset(self::$instance[$author . '*' . $name]))
        {
            // get class namespace
            $class = strval(\App::getNamespace() . 'Modules\\' . $author . '\\' . $name . '\\Module');
            // get version and active from db

            $data = self::getFromDb($author, $name);
            if (!$data)
                return self::$instance[$author . '*' . $name] = null;
            // fill data to module

            $keys = get_object_vars($data);
            $object = new $class;
            foreach ($keys as $key=>$value)
                $object->{$key} = $value;
            self::$instance[$author . '*' . $name] = $object;

            // get data from database

        }
        return self::$instance[$author . '*' . $name];
    }

    /**
     * view template
     * @param $path
     * @param array $params
     * @return View
     */
    public function view($path, array $params)
    {
        return View('module_resource::' . $this->author . '.' . $this->name . '.resources.' . $path, $params);
    }
}
