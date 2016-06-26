<?php

namespace Module\Commands;

use Illuminate\Database\Console\Migrations\MigrateCommand;
use Symfony\Component\Console\Input\InputArgument;

class DbMigrateCommand extends MigrateCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:db:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run a Module Migration files';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Migrate';

    public function fire()
    {
        $inputAuthor = trim($this->ask('Name of author?'));
        $inputName = trim($this->ask('Name of module ?'));
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->prepareDatabase();

        // The pretend option can be used for "simulating" the migration and grabbing
        // the SQL queries that would fire if the migration were to be run against
        // a database for real, which is helpful for double checking migrations.
        $pretend = $this->input->getOption('pretend');
        // get path migrate module
        $path = $this->laravel['path'].'/Modules/'.$inputAuthor.'/'.$inputName.'/db/migrate';
        $this->migrator->run($path, [
            'pretend' => $pretend,
            'step' => $this->input->getOption('step'),
        ]);

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->migrator->getNotes() as $note) {
            $this->output->writeln($note);
        }

        // Finally, if the "seed" option has been given, we will re-run the database
        // seed task to re-populate the database, which is convenient when adding
        // a migration and a seed at the same time, as it is only this command.
        if ($this->input->getOption('seed')) {
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
            ['migrate', InputArgument::OPTIONAL, 'The name of the Migration class'],
        ];
    }
    protected function getMigrationPath()
    {
        if (! is_null($targetPath = $this->input->getOption('path'))) {
            return $this->laravel->basePath().'/'.$targetPath;
        }

        return parent::getMigrationPath();
    }
}
