<?php
/**
 * Created by PhpStorm.
 * User: sharif ahrari
 * Date: 8/19/2016
 * Time: 2:06 AM
 */

namespace Module\Commands;


use Illuminate\Database\Migrations\MigrationCreator;

class ModuleMigrationCreator extends MigrationCreator
{
    protected function getPath($name, $path)
    {
        return $path.'/'.$name.'.php';
    }
}