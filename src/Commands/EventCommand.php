<?php
/**
 * Created by PhpStorm.
 * User: fara
 * Date: 12/14/2016
 * Time: 1:32 PM
 */

namespace Module\Commands;


use Symfony\Component\Console\Input\InputArgument;

class EventCommand extends ModuleCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create event for  Module';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Event';
    protected $event_name;
    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {

        return __DIR__.'/stubs/event.stub';
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

        return str_replace("use $namespace\\Modules;\n", '', parent::buildClass($name));
    }
    public function fire()
    {
        $inputAuthor = trim($this->ask('Author name?'));
        $inputName = trim($this->ask('Module name ?'));
        $event = trim($this->ask('Module event?'));
        $this->event_name = $inputAuthor.'/'.$inputName.'/Events/'.$event;
        $name = $this->parseName($this->event_name);
        $path = $this->getPath($name);

        if ($this->alreadyExists($this->event_name)) {
            $this->error($this->type.' already exists!');

            return false;
        }

        $this->makeDirectory($path);
        // create event file
        $this->files->put($path, $this->buildClass($name));
        // create resource and assets folder and another needed folder
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
}