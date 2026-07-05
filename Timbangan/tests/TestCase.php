<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * (Sebelumnya trait CreatesApplication hilang sehingga seluruh test fatal.)
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // APP_KEY sengaja TIDAK di-hardcode di repo (mencegah kebocoran secret).
        // Bila belum tersedia dari .env (mis. saat CI/Docker), buat key ephemeral
        // sekali jalan — cukup untuk kebutuhan enkripsi selama test berlangsung.
        if (empty($app['config']['app.key'])) {
            $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        }

        return $app;
    }
}
