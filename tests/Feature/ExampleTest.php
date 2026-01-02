<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test the application boots correctly.
     */
    public function test_application_boots(): void
    {
        $this->assertTrue(app()->bound('router'));
    }

    /**
     * Test config is loaded.
     */
    public function test_config_is_loaded(): void
    {
        $this->assertNotEmpty(config('app.name'));
    }
}
