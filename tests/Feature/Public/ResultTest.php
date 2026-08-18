<?php

namespace Tests\Feature\Public;

use App\Models\Event;
use App\Models\Result;
use App\Models\Submission;
use App\Models\Team;
use App\Models\Track;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultTest extends TestCase
{
    use RefreshDatabase;

    private function submissao(Event $event, ?Track $track = null): Submission
    {
        $team = Team::factory()->for($event)->create(['track_id' => $track?->id]);

        return Submission::factory()->for($event)->for($team)->enviada()->create();
    }

    public function test_shows_the_not_published_state_before_publication(): void
    {
        $event = Event::factory()->create(['results_published_at' => null]);

        $response = $this->get(route('resultados.show'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('publicado', false)
            ->where('podio_geral', [])
        );
    }

    public function test_shows_the_overall_podium_after_publication(): void
    {
        $event = Event::factory()->create(['results_published_at' => now()]);
        $submissao = $this->submissao($event);

        $result = Result::factory()->for($event)->for($submissao)->create([
            'final_score' => '8.50',
            'rank_overall' => 1,
        ]);

        $response = $this->get(route('resultados.show'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('publicado', true)
            ->where('podio_geral.0.posicao', 1)
            ->where('podio_geral.0.titulo', $submissao->title)
            ->where('podio_geral.0.equipe', $submissao->team->name)
            ->where('podio_geral.0.nota_final', 8.5)
        );

        $this->assertNotNull($result);
    }

    public function test_a_submission_ranked_below_third_place_is_excluded_from_the_podium(): void
    {
        $event = Event::factory()->create(['results_published_at' => now()]);
        $quarto = $this->submissao($event);
        Result::factory()->for($event)->for($quarto)->create(['final_score' => '5.00', 'rank_overall' => 4]);

        $response = $this->get(route('resultados.show'));

        $response->assertInertia(fn ($page) => $page->where('podio_geral', []));
    }

    public function test_the_track_podium_is_grouped_by_track_name(): void
    {
        $event = Event::factory()->create(['results_published_at' => now()]);
        $trilha = Track::factory()->for($event)->create(['name' => 'Educação']);
        $submissao = $this->submissao($event, $trilha);

        Result::factory()->for($event)->for($submissao)->create([
            'final_score' => '9.00',
            'rank_overall' => 1,
            'rank_track' => 1,
        ]);

        $response = $this->get(route('resultados.show'));

        $response->assertInertia(fn ($page) => $page
            ->has('podio_por_trilha.Educação', 1)
            ->where('podio_por_trilha.Educação.0.titulo', $submissao->title)
        );
    }

    /**
     * Achado no ensaio geral: uma linha de Result com rank_track preenchido
     * mas equipe sem trilha (team->track nulo) derrubava a página inteira
     * com 500 -- team->track->name em cima de null. ComputeResults não
     * deveria mais gerar essa linha, mas a tela não pode confiar só nisso.
     */
    public function test_a_result_with_a_track_rank_but_no_track_never_crashes_the_page(): void
    {
        $event = Event::factory()->create(['results_published_at' => now()]);
        $semTrilha = $this->submissao($event, track: null);

        Result::factory()->for($event)->for($semTrilha)->create([
            'final_score' => '9.00',
            'rank_overall' => 1,
            'rank_track' => 1,
        ]);

        $response = $this->get(route('resultados.show'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('podio_por_trilha', []));
    }

    public function test_the_popular_award_is_hidden_while_voting_is_open(): void
    {
        $event = Event::factory()->create([
            'results_published_at' => now(),
            'voting_opens_at' => now()->subDay(),
            'voting_closes_at' => now()->addDay(),
        ]);
        $submissao = $this->submissao($event);
        Result::factory()->for($event)->for($submissao)->create(['popular_votes_count' => 10]);

        $response = $this->get(route('resultados.show'));

        $response->assertInertia(fn ($page) => $page->where('premio_popular', null));
    }

    public function test_the_popular_award_shows_up_after_voting_closes(): void
    {
        $event = Event::factory()->create([
            'results_published_at' => now(),
            'voting_opens_at' => now()->subDays(2),
            'voting_closes_at' => now()->subDay(),
        ]);
        $submissao = $this->submissao($event);
        Result::factory()->for($event)->for($submissao)->create(['popular_votes_count' => 10]);

        $response = $this->get(route('resultados.show'));

        $response->assertInertia(fn ($page) => $page
            ->where('premio_popular.titulo', $submissao->title)
            ->where('premio_popular.votos', 10)
        );
    }

    public function test_a_specific_past_edition_shows_its_own_results_not_the_current_ones(): void
    {
        $atual = Event::factory()->create(['edition' => 2, 'results_published_at' => null]);
        $passada = Event::factory()->create(['edition' => 1, 'results_published_at' => now()]);
        $submissaoPassada = $this->submissao($passada);
        Result::factory()->for($passada)->for($submissaoPassada)->create([
            'final_score' => '9.20',
            'rank_overall' => 1,
        ]);

        $response = $this->get(route('resultados.show.edicao', $passada));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('publicado', true)
            ->where('evento.nome', $passada->name)
            ->where('podio_geral.0.titulo', $submissaoPassada->title)
        );

        // A rota sem slug continua mostrando a atual, não a passada.
        $this->get(route('resultados.show'))
            ->assertInertia(fn ($page) => $page->where('evento.nome', $atual->name));
    }

    public function test_a_draft_edition_via_slug_shows_the_not_published_state(): void
    {
        $rascunho = Event::factory()->draft()->create(['results_published_at' => null]);

        $response = $this->get(route('resultados.show.edicao', $rascunho));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('publicado', false)
            ->where('podio_geral', [])
        );
    }
}
