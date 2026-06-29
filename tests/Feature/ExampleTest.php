<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Die Wurzel leitet ins Cockpit (Gäste von dort weiter zur Anmeldung).
     */
    public function test_root_redirects_into_cockpit(): void
    {
        $this->get('/')->assertRedirect(route('cockpit.dashboard'));
    }
}
