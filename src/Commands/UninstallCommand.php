<?php

namespace Module\Commands;


use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class UninstallCommand extends MigrateCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:uninstall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uninstall module';

    /**
     * @var object files
     */
    protected $files;

    /**
     * constructor
     * @param Migrator $migrator
     * @param Filesystem $files
     */


    public function __construct(Migrator $migrator, Filesystem $files)
    {
        parent::__construct($migrator);
        $this->files = $files;

    }

    public function fire()
    {
        $inputAuthor = trim($this->ask('Name of author ?'));
        $inputName = trim($this->ask('Name of module ?'));
        // get class name
        $class = $this->getClass($inputAuthor, $inputName);
        if (!class_exists($class))
            $this->error('This module is not found');
        else
        {
            //uninstall migrations
            $this->rollback($inputAuthor, $inputName);
            // run install method
            $module = new $class;
            if (!$module->unInstall($inputAuthor, $inputName))
                $this->error('Error in uninstall module');
            else
            {
                // copy asset files
                $this->removeAssets($inputAuthor,$inputName);
                $this->info('module is uninstalled');
                \Cache::forget('modules');
            }
        }
    }

    protected function rollback($author, $name)
    {
       // @todo
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::OPTIONAL, 'The name of the module'],
            ['author', InputArgument::OPTIONAL, 'The name of the author'],
            ['controller', InputArgument::OPTIONAL, 'The name of the controller'],
        ];
    }

    protected function getClass($author, $name)
    {
        return strval($this->laravel->getNamespace() . 'Modules\\' . $author . '\\' . $name . '\\Module');
    }

    protected function removeAssets($author, $name)
    {
        $path = $this->laravel['path'] . '/Modules/' . $author . '/' . $name . '/assets';
        if ($this->files->exists($path))
        {
            // destination
            $dest = public_path() . '/modules/' . strtolower($author) . '/' . strtolower($name);
            $this->files->deleteDirectory($path, $dest);
        }
    }
}
