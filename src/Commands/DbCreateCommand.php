<?php

namespace Module\Commands;

use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
class DbCreateCommand extends MigrateMakeCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'module:db:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration file';

    /**
     * The migration creator instance.
     *
     * @var \Illuminate\Database\Migrations\MigrationCreator
     */
    protected $creator;

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        // It's possible for the developer to specify the tables to modify in this
        // schema operation. The developer may also specify if this table needs
        // to be freshly created so we can create the appropriate migrations.
        $inputAuthor = trim($this->ask('Name of author?'));
        $inputName = trim($this->ask('Name of module ?'));
        $migreateClass = trim($this->ask('Name of Migrate class?'));
        $table = trim($this->ask('Name of table'));
        $create =true;

        if (! $table && is_string($create)) {
            $table = $create;

            $create = true;
        }

        // Now we are ready to write the migration out to disk. Once we've written
        // the migration out, we will dump-autoload for the entire framework to
        // make sure that the migrations are registered by the class loaders.
        $this->writeMigrate($inputAuthor,$inputName,$migreateClass, $table, $create);

        $this->composer->dumpAutoloads();
    }

    /**
     *  Write the migration file to disk.
     * @param string $author
     * @param string $module
     * @param bool $name
     * @param $table
     * @param $create
     * @return void
     */
    protected function writeMigrate($author,$module,$name, $table, $create)
    {
        $path =  $this->laravel['path'].'/Modules/'.$author.'/'.$module.'/db/migrate';

        $file = pathinfo($this->creator->create($name, $path, $table, $create), PATHINFO_FILENAME);

        $this->line("<info>Created Migration:</info> $file");
    }
    /**
     * Get the full path name to the migration.
     *
     * @param  string  $name
     * @param  string  $path
     * @return string
     */


}
