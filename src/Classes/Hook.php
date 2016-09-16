<?php

namespace Module\Classes;

use LaravelArdent\Ardent\Ardent;

class Hook extends Ardent
{
    //runing hook;
    public static $hookForRun;
    public $timestamps = false;
    protected $table = 'hooks';
    public static $rules = array(
        'name' => 'required|alpha',
        'description' => 'string'
    );

    /**
     * register hook in system
     * @param $hook
     * @param $id_module
     * @return bool
     */
    public static function register($hook, $id_module)
    {
        if (!$id = self::isRegistered($hook))
        {
            // first register hook in database
            $object = new Hook();
            $object->name = $hook;
            if (!$object->save())
            {
                \Log::info($object->errors());
                return false;
            }
            else
                $id = $object->id;
        }
        // we have id now we add data to hooks_modules table
        $position = self::getHighestPosition($hook) + 1;
        \DB::table('hooks_modules')->insert(
            ['id_module' => $id_module, 'id_hook' => $id, 'position' => $position]
        );
        return true;

    }

    /**
     * execute hook and load module relation to that
     * @param $hook
     * @param array $params
     * @return string html or nothing
     */
    public static function execute($hook, $params = array())
    {
        $html = '';
        $modules = Hook::getModulesByName($hook);
        if (!$modules || !is_array($modules) || !count($modules))
            return $html;
        else
        {
            foreach ($modules as $module)
            {
                // check module is exists

                if (\File::exists(app_path('Modules/' . $module->author . '/' . $module->name . '/Module.php')))
                {
                    $object = Module::getInstance($module->author, $module->name);
                    if (method_exists($object, 'hook' . ucfirst($hook)))
                    {
                        $html .= $object->{'hook' . ucfirst($hook)}($params);
                    }
                }

            }
        }
        return $html;
    }

    /**
     * get modules by hook order by position asc
     * @param $hook
     * @return mixed
     */
    public static function getModulesByName($hook)
    {
        Hook::$hookForRun = $hook;
        return  \Cache::remember('hook'.$hook,100000,function(){
            return \DB::table('modules AS m')->select('m.*','hm.position')
                ->leftJoin('hooks_modules AS hm', 'hm.id_module', '=', 'm.id')
                ->leftJoin('hooks AS h', 'h.id', '=', 'hm.id_hook')
                ->where('h.name', Hook::$hookForRun)
                ->where('m.active', '1')
                ->orderBy('hm.position', 'ASC')
                ->get();
        });
    }
    /**
     * check hook is registered in system
     * @param string $hook
     * @return bool
     */
    public static function isRegistered($hook)
    {
        return (int)\DB::table('hooks')->where('name', $hook)->value('id');
    }

    /**
     * get highest position of given hook
     * @param $hook
     * @return int
     */
    public static function getHighestPosition($hook)
    {
        return (int)\DB::table('hooks_modules as hm')->
        leftJoin('hooks as h', 'h.id', '=', 'hm.id_hook')->
        where('h.name',$hook)->
        orderBy('hm.position', 'DESC')->value('position');
    }

    /**
     * get hooks from database
     */
    public static function getHooks()
    {
        return \Cache::remember('hooks',1000,function(){
            return \DB::table('hooks')->get();
        });
    }
}
