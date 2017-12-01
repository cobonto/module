<?php
/**
 * Created by PhpStorm.
 * User: sharif ahrari
 * Date: 9/1/2016
 * Time: 3:58 PM
 */

namespace Module\Classes\Translation;


use Illuminate\Support\Arr;
use Illuminate\Translation\Translator;

class ModuleTranslator extends Translator
{
    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {

        list($namespace, $group, $item) = $this->parseKey($key);
        // add module system
        // Here we will get the locale that should be used for the language line. If one
        // was not passed, we will use the default locales which was given to us when
        // the translator was instantiated. Then, we can load the lines and return.
        $locales = $fallback ? $this->localeArray($locale) : [$locale ?: $this->locale];
        if ($namespace != 'Modules')
        {
            foreach ($locales as $locale)
            {
                $this->load($namespace, $group, $locale);

                $line = $this->getLine(
                    $namespace, $group, $locale, $item, $replace
                );

                if (!is_null($line))
                {
                    break;
                }
            }

            // If the line doesn't exist, we will return back the key which was requested as
            // that will be quick to spot in the UI if language keys are wrong or missing
            // from the application's language files. Otherwise we can return the line.
            if (!isset($line))
            {
                return $key;
            }

            return $line;
        }
        else
        {
            list($namespace, $author, $name, $item) = $this->parseModuleKey($key);

            $locales = $fallback ? $this->localeArray($locale) : [$locale ?: $this->locale];
            foreach ($locales as $locale)
            {
                $this->moduleLoad($author, $name, $locale);

                $line = $this->ModuleGetLine(
                    $author, $name, $locale, $item, $replace
                );

                if (!is_null($line))
                {
                    break;
                }
            }

            // If the line doesn't exist, we will return back the key which was requested as
            // that will be quick to spot in the UI if language keys are wrong or missing
            // from the application's language files. Otherwise we can return the line.
            if (!isset($line))
            {
                return $key;
            }

            return $line;
        }

    }
    protected function ModuleGetLine($author, $name, $locale, $item, array $replace)
    {
        $line = Arr::get($this->loaded['Modules'][$author][$name][$locale], $item);

        if (is_string($line)) {
            return $this->makeReplacements($line, $replace);
        } elseif (is_array($line) && count($line) > 0) {
            return $line;
        }
    }
    /**
     * Load the specified language group for modules.
     *
     * @param  string $author
     * @param  string $name
     * @param  string $locale
     * @return void
     */
    public function moduleLoad($author, $name, $locale)
    {
        if ($this->isModuleLoaded($author, $name, $locale))
        {
            return;
        }

        // The loader is responsible for returning the array of language lines for the
        // given namespace, group, and locale. We'll set the lines in this array of
        // lines that have already been loaded so that we can easily access them.
        $lines = $this->loader->loadModule($locale, $name, $author);
        $this->loaded['Modules'][$author][$name][$locale] = $lines;
    }

    /**
     * Determine if the given module has been loaded.
     *
     * @param  string $namespace
     * @param  string $group
     * @param  string $locale
     * @return bool
     */
    protected function isModuleLoaded($author, $name, $locale)
    {
        return isset($this->loaded['Modules'][$author][$name][$locale]);
    }
    public function parseModuleKey($key)
    {
        $segments = parent::parseKey($key);
        $additional = explode('.',last($segments));
        $segments = array_merge([$segments[0],$segments[1]],$additional);
        return $segments;
    }
}