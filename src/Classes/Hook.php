<?php

namespace Module\Classes;

use LaravelArdent\Ardent\Ardent;

class Hook extends Ardent
{
    use TraitModel;
    public $timestamps = false;
    protected $table='hooks';
    public static $rules = array(
        'name'                  => 'required|alpha',
        'description'           => 'string'
    );

    /**
     * register hook in system
     * @param $hook
     * @param $id_module
     * @return bool
     */
    public static function register($hook,$id_module)
    {
        if(!$id =self::isRegistered($hook))
        {
            \Log::info('No hook '.$hook);
            // first register hook in database
            $object = new Hook();
            $object->name = $hook;
            if(!$object->save())
            {
                \Log::info($object->errors());
                return false;
            }

            else
                $id = $object->id;
            \Log::info('Id hook '.$id);
        }
        // we have id now we add data to hooks_modules table
        $position = self::getHighestPosition('hooks_modules');
        \DB::table('hooks_modules')->insert(
            ['id_module'=>$id_module,'id_hook'=>$id,'position'=>$position]
        );
        return true;

    }

    /**
     * execute hook and load module relation to that
     * @param $hook
     * @param array $params
     * @return string html or nothing
     */
    public static function execute($hook,$params=array())
    {
        $html = '';
       $modules = \DB::table('modules AS m')->select('m.*')
            ->leftJoin('hooks_modules AS hm', 'hm.id_module', '=', 'm.id')
            ->leftJoin('hooks AS h', 'h.id', '=', 'hm.id_hook')
            ->where('h.name',$hook)
           ->where('m.active','1')
           ->orderBy('hm.position','ASC')
            ->get();
        if(!$modules || !is_array($modules) || !count($modules))
            return ;
        else
        {
            foreach($modules as $module)
            {
                // check module is exists

                if(\File::exists(app_path('Modules/'.$module->author.'/'.$module->name.'/Module.php')))
                {
                    $object = Module::getInstance($module->author,$module->name);
                    if(method_exists($object,'hook'.ucfirst($hook)))
                    {
                        $html.=$object->{'hook'.ucfirst($hook)}($params);
                    }
                }

            }
        }
        return $html;
    }
    /**
     * check hook is registered in system
     * @param string $hook
     * @return bool
     */
    public static function isRegistered($hook)
    {
        return (int)\DB::table('hooks')->where('name',$hook)->value('id');
    }
}
