<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (env('APP_ENV') !== 'LOCAL')
            dd('ENVIRONMENT IS NOT LOCAL');
    }
}
