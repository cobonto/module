<?php
namespace Module\Classes\Translation;

class ModuleFileLoader extends \Illuminate\Translation\FileLoader
{
    /**
     * load module file translated
     * @param $path
     * @param $locale
     * @param $name
     * @param $author
     * @return array|mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function loadModulePath($path, $locale, $name,$author)
    {
        if ($this->files->exists($full = "{$path}/{$author}/{$name}/translate/{$locale}.php")) {
            return $this->files->getRequire($full);
        }
        return [];
    }

    /**
     * get file from modules
     * @param $locale
     * @param $name
     * @param $author
     * @return array
     */
    public function loadModule($locale, $name,$author)
    {
        if (isset($this->hints['Modules'])) {
           return $this->loadModulePath($this->hints['Modules'], $locale, $name,$author);
        }

        return [];
    }
}