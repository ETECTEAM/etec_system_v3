<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use RuntimeException;

trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->guardTestDatabase($app);

        return $app;
    }

    /**
     * RefreshDatabase runs `migrate:fresh`, which DROPS EVERY TABLE on the
     * active connection. This project's container gets DB_DATABASE=etec_db
     * injected as a real OS env var by docker-compose, and there is no
     * .env.testing, so phpunit.xml's <env> override is not reliably enough on
     * its own. Force the throwaway schema here and hard-refuse to run against
     * anything that is not obviously disposable.
     */
    protected function guardTestDatabase(Application $app): void
    {
        $connection = $app['config']->get('database.default');
        $key = "database.connections.{$connection}.database";

        $configured = (string) $app['config']->get($key);

        // Only override a real (non-test) name so an intentionally-set
        // *_test / :memory: database is left alone.
        if (! $this->looksDisposable($configured)) {
            $app['config']->set($key, 'etec_db_test');
        }

        $final = (string) $app['config']->get($key);

        if (! $this->looksDisposable($final)) {
            throw new RuntimeException(
                "Refusing to run the test suite against database [{$final}]: the name must ".
                'end in "_test" or be ":memory:". Check phpunit.xml / your env.'
            );
        }
    }

    private function looksDisposable(string $database): bool
    {
        return $database === ':memory:' || str_ends_with($database, '_test');
    }
}
