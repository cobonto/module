<?php

namespace Module\Commands;


use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class InstallCommand extends MigrateCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install module';

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
            // install migrations
            $this->migrate($inputAuthor, $inputName);
            // run install method
            $module = new $class;
            if (!$module->install($inputAuthor, $inputName))
                $this->error('Error in install module');
            else
            {
                // copy asset files
                $this->copyAssets($inputAuthor,$inputName);
                $this->info('module is installed');
            }
        }
    }

    protected function migrate($author, $name)
    {
        if (!$this->confirmToProceed())
        {
            return;
        }

        $this->prepareDatabase();

        // The pretend option can be used for "simulating" the migration and grabbing
        // the SQL queries that would fire if the migration were to be run against
        // a database for real, which is helpful for double checking migrations.
        $pretend = $this->input->getOption('pretend');
        // get path migrate module
        $path = $this->laravel['path'] . '/Modules/' . $author . '/' . $name . '/db/migrate';


        $this->migrator->run($path, [
            'pretend' => $pretend,
            'step' => $this->input->getOption('step'),
        ]);

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->migrator->getNotes() as $note)
        {
            $this->output->writeln($note);
        }

        // Finally, if the "seed" option has been given, we will re-run the database
        // seed task to re-populate the database, which is convenient when adding
        // a migration and a seed at the same time, as it is only this command.
        if ($this->input->getOption('seed'))
        {
            $this->call('db:seed', ['--force' => true]);
        }
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

    protected function copyAssets($author, $name)
    {
        $path = $this->laravel['path'] . '/Modules/' . $author . '/' . $name . '/assets';
        if ($this->files->exists($path))
        {
            // destination
            $dest = public_path() . '/modules/' . strtolower($author) . '/' . strtolower($name);
            $this->files->copyDirectory($path, $dest);
        }
    }
}
