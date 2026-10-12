<?php

namespace Tests\Feature\Console;

use App\Actions\Demo\PrepareDemoStage;
use App\Enums\Role;
use App\Enums\SubmissionStatus;
use App\Enums\TeamStatus;
use App\Models\Attendance;
use App\Models\Checkpoint;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\JudgeAssignment;
use App\Models\Result;
use App\Models\ScheduleItem;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use App\Notifications\TeamDisqualified;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DemoCommandTest extends TestCase
{
    use RefreshDatabase;

    private const SENHA = 'SenhaDemo#2026!';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    private function preparar(string $estagio): void
    {
        $this->artisan('hackathon:demo', ['estagio' => $estagio, '--senha' => self::SENHA])->assertSuccessful();
    }

    private function demo(): Event
    {
        return Event::where('slug', 'hackathon-ifpr-demonstracao')->firstOrFail();
    }

    public function test_it_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('hackathon:demo', ['estagio' => 'inicio', '--senha' => self::SENHA])->assertFailed();

        $this->assertSame(0, Event::where('slug', 'hackathon-ifpr-demonstracao')->count());
    }

    public function test_it_refuses_a_missing_or_short_password(): void
    {
        $this->artisan('hackathon:demo', ['estagio' => 'inicio'])->assertFailed();
        $this->artisan('hackathon:demo', ['estagio' => 'inicio', '--senha' => 'curta'])->assertFailed();

        $this->assertSame(0, Event::where('slug', 'hackathon-ifpr-demonstracao')->count());
    }

    public function test_it_refuses_an_unknown_stage(): void
    {
        $this->artisan('hackathon:demo', ['estagio' => 'qualquer', '--senha' => self::SENHA])->assertFailed();
    }

    public function test_the_password_can_come_from_the_environment_config(): void
    {
        config(['hackathon.demo_password' => self::SENHA]);

        $this->artisan('hackathon:demo', ['estagio' => 'inicio'])->assertSuccessful();

        $this->assertTrue(Hash::check(self::SENHA, User::where('email', 'organizacao.demo@demo.ifpr.test')->firstOrFail()->password));
    }

    public function test_the_start_stage_has_teams_and_no_submissions(): void
    {
        $this->preparar('inicio');
        $evento = $this->demo();

        $this->assertSame(8, Team::forEvent($evento)->count());
        $this->assertSame(0, Submission::forEvent($evento)->count());
        $this->assertTrue($evento->registrationIsOpen());
        $this->assertSame($evento->id, Event::current()->id);
        $this->assertGreaterThanOrEqual(16, EventRegistration::where('event_id', $evento->id)->count());
    }

    public function test_the_three_client_accounts_get_the_demo_password_and_the_right_roles(): void
    {
        $this->preparar('inicio');

        $org = User::where('email', 'organizacao.demo@demo.ifpr.test')->firstOrFail();
        $jurado = User::where('email', 'jurado.demo@demo.ifpr.test')->firstOrFail();
        $participante = User::where('email', 'participante.demo@demo.ifpr.test')->firstOrFail();

        $this->assertTrue($org->hasRole(Role::Organizador->value));
        $this->assertTrue($jurado->hasRole(Role::Jurado->value));
        $this->assertTrue($participante->hasRole(Role::Participante->value));
        foreach ([$org, $jurado, $participante] as $conta) {
            $this->assertTrue(Hash::check(self::SENHA, $conta->password));
        }

        // As demais contas não ficam com a senha conhecida.
        $outra = User::where('email', 'p01.demo@demo.ifpr.test')->firstOrFail();
        $this->assertFalse(Hash::check(self::SENHA, $outra->password));
        $this->assertFalse(Hash::check('password', $outra->password));
    }

    public function test_the_participant_account_leads_a_team(): void
    {
        $this->preparar('inicio');

        $participante = User::where('email', 'participante.demo@demo.ifpr.test')->firstOrFail();

        $this->assertTrue(Team::forEvent($this->demo())->where('leader_id', $participante->id)->exists());
    }

    public function test_the_submissions_stage_has_on_time_late_and_missing_entries(): void
    {
        $this->preparar('submissoes');
        $evento = $this->demo();

        $this->assertSame(7, Submission::forEvent($evento)->count());
        $this->assertSame(6, Submission::forEvent($evento)->where('status', SubmissionStatus::Submitted)->count());
        $this->assertSame(1, Submission::forEvent($evento)->where('status', SubmissionStatus::Late)->count());
        $this->assertSame(0, JudgeAssignment::forEvent($evento)->count());
        $this->assertFalse($evento->submissionIsOpen());
        $this->assertSame(1, Team::forEvent($evento)->whereDoesntHave('submission')->count());
    }

    public function test_the_evaluation_stage_accepts_the_late_entry_and_leaves_the_demo_judge_pending(): void
    {
        $this->preparar('avaliacao');
        $evento = $this->demo();
        $jurado = User::where('email', 'jurado.demo@demo.ifpr.test')->firstOrFail();

        $this->assertSame(1, Submission::forEvent($evento)->where('status', SubmissionStatus::LateAccepted)->count());
        $this->assertGreaterThan(0, JudgeAssignment::forEvent($evento)->count());

        $doJurado = JudgeAssignment::forEvent($evento)->where('judge_id', $jurado->id)->get();
        $this->assertGreaterThan(0, $doJurado->count());
        foreach ($doJurado as $atribuicao) {
            $this->assertNull($atribuicao->evaluation);
        }

        $enviadas = Evaluation::whereIn('assignment_id', JudgeAssignment::forEvent($evento)->pluck('id'))->count();
        $this->assertGreaterThan(0, $enviadas);
        $this->assertSame(0, Result::forEvent($evento)->count());
    }

    public function test_the_result_stage_has_a_ranking_a_disqualified_team_and_attendance_but_is_not_published(): void
    {
        $this->preparar('resultado');
        $evento = $this->demo();

        $this->assertNull($evento->results_published_at);
        $this->assertSame(1, Team::forEvent($evento)->where('status', TeamStatus::Disqualified)->count());
        $this->assertSame(1, Submission::forEvent($evento)->where('status', SubmissionStatus::Disqualified)->count());

        $ranqueadas = Result::forEvent($evento)->whereNotNull('rank_overall')->count();
        $this->assertSame(6, $ranqueadas);
        $this->assertSame(1, Result::forEvent($evento)->where('rank_overall', 1)->count());

        $this->assertGreaterThan(0, Attendance::count());
        $this->assertTrue($evento->votingIsOpen());
    }

    public function test_the_ranking_is_the_same_on_every_run(): void
    {
        $this->preparar('resultado');
        $primeiro = Result::forEvent($this->demo())->with('submission.team')->orderBy('rank_overall')->get()
            ->map(fn (Result $r) => $r->submission->team->name.':'.$r->final_score)->all();

        $this->preparar('resultado');
        $segundo = Result::forEvent($this->demo())->with('submission.team')->orderBy('rank_overall')->get()
            ->map(fn (Result $r) => $r->submission->team->name.':'.$r->final_score)->all();

        $this->assertSame($primeiro, $segundo);
    }

    public function test_going_back_to_the_start_wipes_everything_of_the_demo_edition(): void
    {
        $this->preparar('resultado');
        $this->preparar('inicio');
        $evento = $this->demo();

        $this->assertSame(8, Team::forEvent($evento)->count());
        $this->assertSame(0, Submission::forEvent($evento)->count());
        $this->assertSame(0, JudgeAssignment::forEvent($evento)->count());
        $this->assertSame(0, Result::forEvent($evento)->count());
        $this->assertSame(0, Attendance::count());
        $this->assertNull($evento->results_published_at);
        $this->assertSame(0, Team::forEvent($evento)->where('status', TeamStatus::Disqualified)->count());
    }

    public function test_it_never_touches_another_edition(): void
    {
        $real = Event::factory()->create(['edition' => 1, 'slug' => 'edicao-real']);
        $equipeReal = Team::factory()->for($real)->create(['name' => 'Equipe Real']);
        $submissaoReal = Submission::factory()->for($real)->for($equipeReal)->enviada()->create();

        $this->preparar('resultado');
        $this->preparar('inicio');

        $this->assertModelExists($equipeReal);
        $this->assertModelExists($submissaoReal);
        $this->assertSame(TeamStatus::Draft, $equipeReal->fresh()->status);
    }

    public function test_it_keeps_a_real_users_account(): void
    {
        $real = User::factory()->create(['email' => 'pessoa@exemplo.com', 'email_verified_at' => now()]);
        $real->assignRole(Role::Participante->value);

        $this->preparar('inicio');
        $this->preparar('resultado');

        $this->assertModelExists($real);
        $this->assertTrue($real->fresh()->hasRole(Role::Participante->value));
    }

    public function test_the_action_itself_refuses_outside_the_allowed_environments(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(\RuntimeException::class);

        app(PrepareDemoStage::class)->handle('inicio', self::SENHA);
    }

    /** Montar o cenário não pode encher a caixa de e-mails a cada reinício. */
    public function test_preparing_a_stage_sends_no_e_mails(): void
    {
        $this->preparar('resultado');
        $this->preparar('resultado');

        Notification::assertNothingSent();
    }

    /** Já a desclassificação feita ao vivo pelo organizador continua avisando a equipe. */
    public function test_a_live_disqualification_after_the_stage_still_notifies_the_team(): void
    {
        $this->preparar('resultado');
        $ativa = Team::forEvent($this->demo())->where('status', TeamStatus::Confirmed)->firstOrFail();
        $organizacao = User::where('email', 'organizacao.demo@demo.ifpr.test')->firstOrFail();

        $this->actingAs($organizacao)
            ->post(route('painel.teams.disqualify', $ativa), ['motivo' => 'Teste da demonstração ao vivo.'])
            ->assertSessionHas('sucesso');

        Notification::assertSentTo(
            User::whereIn('id', $ativa->activeMemberships()->pluck('user_id'))->get(),
            TeamDisqualified::class
        );
    }

    public function test_the_demo_schedule_and_entry_checkpoint_use_brasilia_wall_clock(): void
    {
        $this->preparar('resultado');

        $entrada = Checkpoint::where('name', 'Entrada')->firstOrFail();
        $abertura = ScheduleItem::where('title', 'Abertura e boas-vindas')->firstOrFail();

        $this->assertSame('08:00', $entrada->starts_at->copy()->timezone('America/Sao_Paulo')->format('H:i'));
        $this->assertSame('09:00', $abertura->starts_at->copy()->timezone('America/Sao_Paulo')->format('H:i'));
    }
}
