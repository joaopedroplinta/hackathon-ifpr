<?php

namespace Tests\Feature\Public;

use App\Support\AppVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ChangelogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_the_versions_page(): void
    {
        $this->get(route('versoes.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('publico/versoes')
                ->has('versoes')
                ->where('atual', (new AppVersion)->numero()));
    }

    /** Release sem entrada no CHANGELOG (ou bump esquecido) quebra aqui. */
    public function test_latest_listed_version_matches_composer_json(): void
    {
        $this->get(route('versoes.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('versoes.0.numero', (new AppVersion)->numero()));
    }
}
