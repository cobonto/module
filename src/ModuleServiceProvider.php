<?php

namespace Module;

use Module\Commands\ControllerCommand;
use Module\Commands\DbCreateCommand;
use Module\Commands\DbMigrateCommand;
use Module\Commands\InstallCommand;
use Module\Commands\ModelCommand;
use Module\Commands\ModuleMigrationCreator;
use Module\Commands\NewCommand;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\ServiceProvider;
use Module\Commands\UninstallCommand;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->registerViewFinder();
        //
        $this->publishes([
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRepository();
        $this->registerMigrator();
        // for create migration
        $this->registerCreator();
        // for create migration

        // add commands
        $this->registerCommands();

    }

    protected function registerRepository()
    {
        $this->app->bindIf('migration.repository', function ($app)
        {
            $table = $app['config']['database.migrations'];

            return new DatabaseMigrationRepository($app['db'], $table);
        });
    }

    protected function registerMigrator()
    {
        // The migrator is responsible for actually running and rollback the migration
        // files in the application. We'll pass in our database connection resolver
        // so the migrator can resolve any of these connections when it needs to.
        $this->app->bindIf('migrator', function ($app)
        {
            $repository = $app['migration.repository'];

            return new Migrator($repository, $app['db'], $app['files']);
        });
    }

    /**
     * Register the migration creator.
     *
     * @return void
     */
    protected function registerCreator()
    {
        $this->app->bindIf('module.migration.creator', function ($app)
        {
            return new ModuleMigrationCreator($app['files']);
        });
    }

    protected function registerCommands()
    {
        $commands = ['Install', 'Model', 'Migration', 'New', 'CreateMigration', 'Controller','Uninstall'];
        foreach ($commands as $command)
        {
            $this->{'register' . $command . 'Command'}();
        }

        $this->commands(
            'module.db.migrate',
            'module.db.create',
            'module.new',
            'module.controller',
            'module.model',
            'module.install',
            'module.uninstall'
        );
    }

    protected function registerMigrationCommand()
    {
        $this->app->singleton('module.db.migrate', function ($app)
        {
            return new DbMigrateCommand($app['migrator']);
        });
    }

    protected function registerInstallCommand()
    {
        $this->app->singleton('module.install', function ($app)
        {
            return new InstallCommand($app['migrator'],$app['files']);
        });
    }
    protected function registerUninstallCommand()
    {
        $this->app->singleton('module.uninstall', function ($app)
        {
            return new UninstallCommand($app['migrator'],$app['files']);
        });
    }
    protected function registerCreateMigrationCommand()
    {
        $this->app->singleton('module.db.create', function ($app)
        {
            return new DbCreateCommand($app['module.migration.creator'], $app['composer']);
        });
    }

    protected function registerNewCommand()
    {
        $this->app->singleton('module.new', function ($app)
        {
            return new NewCommand($app['files']);
        });
    }

    protected function registerControllerCommand()
    {
        $this->app->singleton('module.controller', function ($app)
        {
            return new ControllerCommand($app['files']);
        });
    }

    protected function registerModelCommand()
    {
        $this->app->singleton('module.model', function ($app)
        {
            return new ModelCommand($app['files']);
        });
    }
    protected function registerViewFinder()
    {
        view()->addNamespace('module_resource', app_path('Modules'));
    }
}
