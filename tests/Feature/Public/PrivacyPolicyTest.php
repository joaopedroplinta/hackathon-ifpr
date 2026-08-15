<?php

namespace Tests\Feature\Public;

use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    public function test_the_page_renders(): void
    {
        $this->get(route('privacidade.show'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('publico/privacidade'));
    }
}
