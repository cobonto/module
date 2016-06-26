<?php

namespace Module\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

class NewCommand extends ModuleCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:new';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Module';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Module';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {

        return __DIR__.'/stubs/module.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Modules';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [

        ];
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in base namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $namespace = $this->getNamespace($name);

        return str_replace("use $namespace\Modules;\n", '', parent::buildClass($name));
    }
    public function fire()
    {
        $inputAuthor = trim($this->ask('Name of author?'));
        $inputName = trim($this->ask('Name of module ?'));
        $this->module_name = $inputAuthor.'/'.$inputName.'/Module';
        $name = $this->parseName($this->module_name);

        $path = $this->getPath($name);

        if ($this->alreadyExists($this->module_name)) {
            $this->error($this->type.' already exists!');

            return false;
        }

        $this->makeDirectory($path);
        // create Module.php file
        $this->files->put($path, $this->buildClass($name));
        // create module.yml file
        $this->makeYml($path,$inputAuthor,$inputName);
        // create resource and assets folder and another needed folder
        $this->makeSubDirs($path);
        $this->info($this->type.' created successfully.');
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
        ];
    }

    /**
     * create yml file info about module
     * @param $path
     * @param $inputAuthor
     * @param $inputName
     */
    protected function makeYml($path,$inputAuthor,$inputName)
    {
        $data = [
            'name'=>$inputName,
            'author'=>$inputAuthor,
            'version'=>1.0,

        ];
        $yml = Yaml::dump($data);
        $this->files->put(dirname($path).'/module.yml',$yml);
    }

    public function makeSubDirs($path)
    {
        $directories =[
            'resources',
            'translate',
            'assets',
            'db',
            'db/migrate',
            'db/seed',
        ];
        foreach($directories as $directory)
        {
            if (! $this->files->isDirectory(dirname($path).'/'.$directory)) {
                $this->files->makeDirectory(dirname($path).'/'.$directory, 0777, true, true);
            }
        }
    }
}
