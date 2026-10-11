<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SeederProductionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_seeders_refuse_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        foreach ([DatabaseSeeder::class, DemoSeeder::class] as $seeder) {
            try {
                app($seeder)->run();
                $this->fail("{$seeder} rodou em produção.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('produção', $e->getMessage());
            }
        }

        $this->assertSame(0, User::where('email', 'organizacao@ifpr.edu.br')->count());
    }
}
