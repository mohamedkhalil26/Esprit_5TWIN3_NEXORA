<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        if ($app->configurationIsCached() || config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:' || config('database.connections.sqlite.url')) {
            throw new \RuntimeException('Les tests exigent exclusivement SQLite en mémoire.');
        }

        config([
            'ai.fallback_model' => 'google/gemma-4-26b-a4b-it:free',
        ]);

        return $app;
    }
}
