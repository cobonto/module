<?php

namespace Module\Classes;

use Cobonto\Classes\Assign;
use Cobonto\Classes\Traits\HelperForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use LaravelArdent\Ardent\Ardent;
use Symfony\Component\Yaml\Yaml;

class Module extends Ardent
{
    use HelperForm;
    //
    protected $table = 'modules';
    public $timestamps = false;
    // local path
    protected $localPath;
    // name of module folder
    public $name;
    // name of author folder
    public $author;
    // version
    public $version;
    // array of loaded modules instance to prevent instance again
    protected static $instance = [];
    // error
    public $errors = [];
    /** @var Assign */
    protected $assign;
    /** @var array config modules */
    public $configs;
    /** @var array hooks */
    protected $hooks;
    /** @var string prefix */
    public $prefix;
    /** @var string mediaPath */
    public $mediaPath;
    /** @var bool core module  */
    public $core = false;
    /** @var string $namespaceController */
    protected $nameSpaceControllers;
    /** @var string namespace */
    protected $nameSpace;
    /** @var array rules */
    public static $rules = [
        'name' => 'required|string',
        'author' => 'required|string',
        'version' => 'required|string',
        'active' => 'required|integer',
    ];

    public function __construct(array $attributes = [])
    {
        $this->localPath = app_path() . '/Modules/' . $this->author . '/' . $this->name . '/';
        $this->assign = app('assign');
        $this->mediaPath = 'modules/' . strtolower($this->author) . '/' . strtolower($this->name) . '/';
        $this->prefix = strtoupper($this->author) . '_' . strtoupper($this->name) . '_';
        $this->nameSpaceControllers = app()->getNamespace().'Modules\\'.$this->author.'\\'.$this->name.'\Controllers\\';
        $this->nameSpace = app()->getNamespace().'Modules\\'.$this->author.'\\'.$this->name.'\\';
        parent::__construct($attributes);
        // get id from database
        $data = Module::getFromDb($this->author, $this->name);
        if ($data)
            $this->id = $data->id;
        else
            $this->id = null;
        $this->bootModule();
    }

    protected function bootModule()
    {

    }

    // install method
    public function install()
    {
        // check module is installed
        if (!self::checkOnDisk($this->author, $this->name))
        {
            $this->errors[] = 'Module not found';
            return false;
        }
        if ($this->id)
        {
            $this->errors[] = 'Module is currently installed';
            return false;
        }
        // migrate
        //register events
        if(!$this->registerEvents())
            return false;
        $this->migrate();
        // add module in table modules
        {
            $data = [
                'name' => $this->name,
                'author' => $this->author,
                'version' => $this->version,
                'active' => 1,
            ];
            if (!\DB::table('modules')->insert($data))
            {
                $this->errors[] = 'Problem install module';
                return false;
            }
            else
            {
                // get for module
                $data = Module::getFromDb($this->author, $this->name);
                $this->id = $data->id;
                // install configurations
                if (!$this->installConfigure())
                    return false;
                // add assets
                if (!$this->copyAssets())
                    return false;
                // register hooks
                if (count($this->hooks))
                    $this->registerHooks($this->hooks);

                return true;
            }

        }
    }

    public function unInstall()
    {
        if($this->core)
        {
            $this->errors[] = 'Can not uninstall core modules';
            return false;
        }
        // check module is installed
        if (!Module::checkOnDisk($this->author, $this->name))
        {
            $this->errors[] = 'Module not found';
            return false;
        }
        if ($this->id)
        {
            $this->migrate('down');
            if(!$this->unRegisterEvents())
            {
                $this->errors[] = 'problem in unregister Events';
                return false;
            }
            if (!$this->uninstallConfigure())
            {
                $this->errors[] = 'Problem in delete configuration';
                return false;
            }
            if (!$this->deleteModule())
            {
                $this->errors[] = 'Problem in uninstalling module';
                return false;
            }
            else
                return true;
        }
        else
        {
            $this->errors[] = 'Module is currently is uninstalled';
            return false;
        }

    }

    protected function installConfigure()
    {
        if (!count($this->configs))
            return true;
        else
        {
            foreach ($this->configs as $key => $value)
            {
                if (!app('settings')->set($this->prefix . $key, $value))
                {
                    $this->errors[] = 'Problem add settings';
                    return false;
                }
            }
            return true;

        }
    }

    protected function uninstallConfigure()
    {
        if (!count($this->configs))
            return true;
        else
        {
            foreach ($this->configs as $key => $value)
            {
                app('settings')->deleteByName($this->prefix . $key);
            }
            return true;

        }
    }

    /**
     * check module is disk or not
     * @param $author
     * @param $name
     * @return bool
     */
    public static function checkOnDisk($author, $name)
    {
        return (bool)app('files')->exists(app_path() . '/Modules/' . $author . '/' . $name . '/Module.php');
    }

    /**
     * @param array $hooks
     * @return bool
     */
    public function registerHooks(array $hooks)
    {
        if(count($this->hooks))
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
        if (!self::checkOnDisk($author, $name))
            return false;
        return self::getFromDb($author, $name);
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
     * @return bool|Module
     */
    public static function getInstance($author, $name)
    {
        // check module is exist in host
        if (!self::checkOnDisk($author, $name))
            return null;
        if (!isset(self::$instance[$author . '*' . $name]))
        {
            // get class namespace
            $class = strval(\App::getNamespace() . 'Modules\\' . $author . '\\' . $name . '\\Module');
            $object = new $class;
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
    public function view($path, array $params = [])
    {
        $params = array_add($params, 'module', $this);
        return view('module_resource::' . $this->author . '.' . $this->name . '.resources.' . $path, $params);
    }

    /**
     * get from disk
     */
    public static function getModulesFromDisk()
    {
        // check Modules folder is created or not
        if(!app('files')->exists(app_path('Modules')))
            app('files')->makeDirectory(app_path('Modules'));
        $authors = app('files')->directories(app_path('Modules'));
        $results = [];
        if ($authors && count($authors))
        {
            foreach ($authors as $author)
            {
                $authorName = basename($author);
                $modules = app('files')->directories($author);
                if ($modules && count($modules))
                {
                    foreach ($modules as $module)
                    {
                        // check module yaml
                        $ymlPath = $module . '/module.yml';
                        if (app('files')->exists($ymlPath))
                        {
                            $detail = Yaml::parse(app('files')->get($ymlPath));
                            $results[$authorName][] = $detail;
                        }
                    }
                }
            }
        }
        return $results;
    }

    /**
     *   delete module
     */
    protected function deleteModule()
    {
        return \DB::table('modules')->delete($this->id);
    }

    /**
     * prepare and render configuration form
     */
    protected function renderConfigure()
    {
        $this->fillValues();
        $this->tpl_form = 'admin.helpers.form.form';
        $this->generateForm();
        // assign some vars
        $this->assign->params([
            'form_url' => route(config('app.admin_url').'.modules.save', ['author' => strtolower(camel_case($this->author)), 'name' => strtolower(camel_case($this->name))]),
            'route_list' => route(config('app.admin_url').'.modules.index'),
        ]);
        return view($this->tpl_form, $this->assign->getViewData());
    }

    // save configure
    public function saveConfigure(Request $request)
    {
        // check for prepare switchers
        $request = $this->calcPost($request);
        foreach ($this->configs as $config => $value)
        {
            app('settings')->set($this->prefix . $config, $request->input($config));
        }
        return 'Update successfully';
    }

    /**
     * fill values
     */
    protected function fillValues()
    {
        if ($this->configs && count($this->configs))
            foreach ($this->configs as $key => $value)
                $this->fields_values[$key] = app('settings')->get($this->prefix . $key);
    }

    /**
     * migrate files from db/migration folder
     * @param string $type
     */
    protected function migrate($type = 'up')
    {
        $files = app('files')->allFiles($this->localPath . '/db/migrate');
        if ($files && count($files))
        {
            foreach ($files as $file)
            {
                require_once($file->getPathName());
                $class = explode('.', $file->getFileName());
                $class = $class[0];
                $migrate = new $class;
                if ($type == 'up')
                    $migrate->up();
                else
                    $migrate->down();
            }
        }
    }

    protected function copyAssets()
    {
        $path = $this->localPath . '/assets';
        if (app('files')->exists($path))
        {
            // destination
            $dest = public_path() . '/modules/' . strtolower($this->author) . '/' . strtolower($this->name);
            return app('files')->copyDirectory($path, $dest);
        }
        return true;
    }

    /**
     * get translated string
     * @param $string
     * @return string|\Symfony\Component\Translation\TranslatorInterface
     */
    public function lang($string)
    {
        return trans('Modules::' . $this->author . '.' . $this->name . '.' . $string);
    }

    /**
     * add css file for module
     * @param $files
     */
    public function addCSS($files)
    {
        if(!is_array($files))
            $files = [$files];
        foreach($files as $key=>$file)
        {
            $files[$key] = $this->mediaPath.'css/'.$file;
        }
        $this->assign->addCSS($files,true);
    }
    /**
     * add js file for module
     * @param $files
     */
    public function addJS($files)
    {
        if(!is_array($files))
            $files = [$files];
        foreach($files as $key=>$file)
        {
            $files[$key] = $this->mediaPath.'js/'.$file;
        }
        $this->assign->addJS($files,true);
    }
    /**
     * add plugin file for module
     * @param $files
     */
    public function addPlugin($file)
    {
        $path = $this->mediaPath.'plugins/';
        $this->assign->addJS($path . $file . '/' . $file . '.min.js',true);
        $this->assign->addCSS($path . $file . '/' . $file . '.css',true);
    }

    /**
     * regenerate cache
     *  @return array
     */
    public static function getModules()
    {
        return \Cache::remember('modules', 604800, function ()
        {
            $diskModules = Module::getModulesFromDisk();
            if ($diskModules)
                return self::checkModulesInDb($diskModules);
            else
                return [];
        });
    }
    protected static function checkModulesInDb($modules)
    {
        foreach ($modules as $author => &$module)
        {
            foreach ($module as &$subModule)
            {
                if ($data = Module::isInstalled($author, $subModule['name']))
                {
                    $subModule['installed'] = 1;
                    $subModule['active'] = $data->active;
                    $moduleClass = Module::getInstance($author, $subModule['name']);
                    if (is_object($moduleClass))
                    {
                        if (method_exists($moduleClass, 'configuration'))
                            $subModule['configurable'] = 1;

                        $subModule['core']=$moduleClass->core;
                    }
                }
            }
        }
        return $modules;
    }
    /**
     * Get name of all modules that exists
     * @param string $file that must be exits
     * @return array
     */
    public static function getModulesByFile($file = 'Module.php')
    {
        $modules = Module::getModules();
        $source = app_path('Modules' . DIRECTORY_SEPARATOR . '{author}' . DIRECTORY_SEPARATOR . '{module}' . DIRECTORY_SEPARATOR . $file);
        $files = [];
        if ($modules && count($modules))
            foreach ($modules as $author => $subModules)
            {
                foreach ($subModules as $module)
                {
                    $data = ['{author}' => $module['author'], '{module}' => $module['name']];
                    $real_source = str_replace(array_keys($data), array_values($data), $source);
                    if (\File::exists($real_source))
                    {
                        $files[] = [
                            'author'=>$author,
                            'module'=>$module,
                            'name'=>$author . DIRECTORY_SEPARATOR . $module['name'],
                            'installed'=>isset($module['installed']),
                        ];
                    }
                }
            }
        return $files;
    }
    /** events */
    public function events()
    {
        return [];
    }
    /**
     * register events to system
     * @return bool
     */
    protected function registerEvents()
    {
        if(is_array($this->events()) && count($this->events()))
        {
            $event = new Event($this);
            return $event->addEvents();
        }
        return true;
    }
    /**
     * register events to system
     * @return bool
     */
    protected function unRegisterEvents()
    {
        if(is_array($this->events()) && count($this->events()))
        {
            $event = new Event($this);
            return $event->removeEvents();
        }
        return true;
    }
}
