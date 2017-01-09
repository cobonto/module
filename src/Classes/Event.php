<?php


namespace Module\Classes;


class Event
{
    /**
     * @var Module $module
    */
    protected $module;
    public function __construct($module)
    {
        $this->module = $module;
    }
    // add events to events.php in cache
    public function addEvents()
    {
        if($moduleEvents = $this->module->events())
        {
            $systemEvents = $this->loadEvents();
            if(isset($moduleEvents['listen']))
            // for listen event we must check if exsits merge if not add it
            foreach ($moduleEvents['listen'] as $event => $listens)
            {
                if(isset($systemEvents['listen'][$event]))
                    $systemEvents['listen'][$event] = array_merge($systemEvents['listen'][$event],$listens);
                else
                    $systemEvents['listen'][$event] = $listens;
            }
            // for subscribe
            if(isset($moduleEvents['subscribe']))
                $systemEvents['subscribe'][] = $moduleEvents['subscribe'];
            return $this->setEventsCached($systemEvents);
        }
        return true;
    }
    // remove events from system
    public function removeEvents()
    {
        if($moduleEvents = $this->module->events())
        {
            $systemEvents = $this->loadEvents();
            if (isset($moduleEvents['listen']))
                // for listen event we must check if exsits merge if not add it
                foreach ($moduleEvents['listen'] as $event => $listens)
                {
                    if (isset($systemEvents['listen'][$event]))
                    {
                        foreach ($listens as $listen)
                        {
                            $key = array_search($listen, $systemEvents['listen'][$event]);
                            if ($key !== false)
                                unset($listen, $systemEvents['listen'][$event][$key]);
                        }
                    }
                }
            // for subscribe
            if (isset($moduleEvents['subscribe']))
            {
                $key = array_search($moduleEvents['subscribe'], $systemEvents['subscribe'][$event]);
                if($key !==false)
                    unset($systemEvents['subscribe'][$key]);
            }
            return $this->setEventsCached($systemEvents);
        }
        return true;
    }
    public function loadEvents()
    {
       return app('files')->getRequire($this->getEventCachedPath());
    }
    protected function getEventCachedPath()
    {
        return app('path.bootstrap').DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'events.php';
    }
    protected function setEventsCached($data)
    {
       return app('files')->put($this->getEventCachedPath(),'<?php'.PHP_EOL.'return '.var_export($data,true).';'.PHP_EOL);
    }
}