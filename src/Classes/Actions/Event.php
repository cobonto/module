<?php


namespace Module\Classes\Actions;


class Event extends Action
{
    protected $file='events';
    // add events to events.php in cache
    public function add()
    {
        if($moduleEvents = $this->module->events())
        {
            $systemEvents = $this->load();
            if(isset($moduleEvents['listen']))
            // for listen event we must check if exists merge if not add it
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
            return $this->set($systemEvents);
        }
        return true;
    }
    // remove events from system
    public function remove()
    {
        if($moduleEvents = $this->module->events())
        {
            $systemEvents = $this->load();
            if (isset($moduleEvents['listen']))
                // for listen event we must check if exists merge if not add it
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
            return $this->set($systemEvents);
        }
        return true;
    }
}