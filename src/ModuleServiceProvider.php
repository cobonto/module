<?php

namespace Module;

use Module\Classes\Translation\ModuleFileLoader;
use Module\Classes\Translation\ModuleTranslator;
use Module\Commands\ControllerCommand;
use Module\Commands\DbCreateCommand;
use Module\Commands\DbMigrateCommand;
use Module\Commands\EventCommand;
use Module\Commands\InstallCommand;
use Module\Commands\ListenerCommand;
use Module\Commands\ModelCommand;
use Module\Commands\ModuleMigrationCreator;
use Module\Commands\NewCommand;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
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
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRepository();
        $this->registerMigrator();
        $this->registerTranslation();
        // for create migration
        $this->registerCreator();
        // for create migration

        // add commands
        $this->registerCommands();


    }
    public function boot()
    {
        // register language source
        $this->loadTranslationsFrom($this->app->basePath().'/app/Modules','Modules');
        //
        $this->registerViewFinder();
        //
        if (! $this->app->routesAreCached()) {
            if (file_exists(base_path().DIRECTORY_SEPARATOR.'.env') && config('app.install'))
            {
                require __DIR__ . '/../routes.php';
            }
        }
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
        $commands = ['Install', 'Model', 'Migration', 'New',
                    'CreateMigration', 'Controller','Uninstall'
                    ,'Event'];
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
            'module.uninstall',
            'module.event'
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
    protected function registerEventCommand()
    {
        $this->app->singleton('module.event', function ($app)
        {
            return new EventCommand($app['files']);
        });
    }
    protected function registerListenerCommand()
    {
        $this->app->singleton('module.listener', function ($app)
        {
            return new ListenerCommand($app['files']);
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
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new ModuleFileLoader($app['files'], $app['path.lang']);
        });
    }
    protected function registerTranslation()
    {
        $this->registerLoader();
        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            // When registering the translator component, we'll need to set the default
            // locale as well as the fallback locale. So, we'll grab the application
            // configuration so we can easily get both of these values from there.
            $locale = $app['config']['app.locale'];

            $trans = new ModuleTranslator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['translator', 'translation.loader'];
    }
}
