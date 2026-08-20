<?php

namespace Tests\Feature\Participant;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Submission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function inscrito(Event $event): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        EventRegistration::factory()->for($event)->for($user)->create();

        return $user;
    }

    public function test_sem_evento_publicado_a_trilha_vem_nula()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('dashboard')->where('trilha', null));
    }

    public function test_usuario_nao_inscrito_ve_so_o_passo_de_inscricao_disponivel()
    {
        $event = Event::factory()->aberto()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('dashboard')
                ->has('trilha', 5)
                ->where('trilha.0.chave', 'inscricao')
                ->where('trilha.0.status', 'disponivel')
                ->where('trilha.1.status', 'bloqueado')
                ->where('trilha.2.status', 'bloqueado')
                ->where('trilha.3.status', 'bloqueado')
            );
    }

    public function test_inscrito_sem_equipe_ve_equipe_e_credencial_disponiveis()
    {
        $event = Event::factory()->aberto()->create();
        $user = $this->inscrito($event);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('dashboard')
                ->where('trilha.0.status', 'concluido')
                ->where('trilha.1.status', 'disponivel')
                ->where('trilha.2.status', 'disponivel')
                ->where('trilha.3.status', 'bloqueado')
            );
    }

    public function test_equipe_com_submissao_enviada_marca_os_passos_como_concluidos()
    {
        $event = Event::factory()->aberto()->create();
        $leader = $this->inscrito($event);
        $team = Team::factory()->for($event)->create(['leader_id' => $leader->id]);
        TeamMember::factory()->for($event)->for($team)->for($leader)->lider()->create();
        Submission::factory()->for($event)->for($team)->enviada()->create();

        $this->actingAs($leader)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('dashboard')
                ->where('trilha.0.status', 'concluido')
                ->where('trilha.1.status', 'concluido')
                ->where('trilha.3.status', 'concluido')
                ->where('trilha.4.status', 'bloqueado')
            );
    }

    public function test_resultado_publicado_libera_o_ultimo_passo()
    {
        $event = Event::factory()->resultadosPublicados()->create();
        $user = $this->inscrito($event);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('dashboard')
                ->where('trilha.4.status', 'disponivel')
            );
    }
}
