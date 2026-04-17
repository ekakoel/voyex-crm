<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();
        $this->guardUnsafeTestingDatabase($app);

        return $app;
    }

    private function guardUnsafeTestingDatabase(Application $app): void
    {
        if (! $app->environment('testing')) {
            return;
        }

        $defaultConnection = (string) $app['config']->get('database.default');
        $connection = (array) $app['config']->get("database.connections.{$defaultConnection}", []);
        $driver = (string) ($connection['driver'] ?? $defaultConnection);
        $database = (string) ($connection['database'] ?? '');

        $isSafeSqlite = $driver === 'sqlite'
            && ($database === ':memory:' || str_ends_with(str_replace('\\', '/', $database), '/database/testing.sqlite'));

        if ($isSafeSqlite) {
            return;
        }

        if (! str_contains(strtolower($database), 'test')) {
            throw new RuntimeException(sprintf(
                'Unsafe test database detected: connection "%s" (driver: %s, database: %s). Configure a dedicated test database first.',
                $defaultConnection,
                $driver,
                $database !== '' ? $database : '[empty]'
            ));
        }
    }
}
