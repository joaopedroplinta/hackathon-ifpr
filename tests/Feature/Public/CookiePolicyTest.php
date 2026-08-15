<?php

namespace Tests\Feature\Public;

use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CookiePolicyTest extends TestCase
{
    public function test_the_page_renders(): void
    {
        $this->get(route('cookies.show'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('publico/cookies'));
    }
}
