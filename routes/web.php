<?php

use App\Http\Controllers\Judge\EvaluationController;
use App\Http\Controllers\Organizer\CertificateController as OrganizerCertificateController;
use App\Http\Controllers\Organizer\CheckinController;
use App\Http\Controllers\Organizer\DashboardController;
use App\Http\Controllers\Organizer\EventController;
use App\Http\Controllers\Organizer\IncidentController;
use App\Http\Controllers\Organizer\JudgeAssignmentController;
use App\Http\Controllers\Organizer\ManualSubmissionController;
use App\Http\Controllers\Organizer\ResultController;
use App\Http\Controllers\Organizer\RubricController as OrganizerRubricController;
use App\Http\Controllers\Organizer\ScheduleItemController;
use App\Http\Controllers\Organizer\SubmissionController as OrganizerSubmissionController;
use App\Http\Controllers\Organizer\UserController as OrganizerUserController;
use App\Http\Controllers\Participant\CertificateController;
use App\Http\Controllers\Participant\CredentialController;
use App\Http\Controllers\Participant\DashboardController as ParticipantDashboardController;
use App\Http\Controllers\Participant\EventRegistrationController;
use App\Http\Controllers\Participant\PopularVoteController;
use App\Http\Controllers\Participant\SubmissionController;
use App\Http\Controllers\Participant\SubmissionFileController;
use App\Http\Controllers\Participant\TeamController;
use App\Http\Controllers\Participant\TeamInviteController;
use App\Http\Controllers\Participant\TeamLeadershipController;
use App\Http\Controllers\Participant\TeamMembershipController;
use App\Http\Controllers\Public\AgendaController;
use App\Http\Controllers\Public\CertificateValidationController;
use App\Http\Controllers\Public\CookiePolicyController;
use App\Http\Controllers\Public\EditionController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\PrivacyPolicyController;
use App\Http\Controllers\Public\RegulationController;
use App\Http\Controllers\Public\ResultController as PublicResultController;
use App\Http\Controllers\Public\RubricController as PublicRubricController;
use App\Http\Controllers\Public\SubmissionShowcaseController;
use App\Http\Middleware\EnsureEventExists;
use App\Models\Submission;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'show'])->name('home');

// Agenda pública. Sem 'auth': é o que a tabela de papéis promete pro guest
// (PLANO.md, seção 3) -- ver agenda de novo/oficinas antes mesmo de se inscrever.
Route::get('agenda', [AgendaController::class, 'index'])->name('agenda.index');
Route::get('agenda.ics', [AgendaController::class, 'ics'])->name('agenda.ics');

// Rubrica pública -- .claude/rules do Anexo A: reduz disputa sobre nota
// deixar os critérios visíveis desde antes do evento.
Route::get('rubrica', [PublicRubricController::class, 'show'])->name('rubrica.show');

// Regulamento público -- mesmo motivo da rubrica acima: critério de
// desempate e regra do Degrau 0 (plano B) precisam estar visíveis antes da
// inscrição, não só espalhados no plano interno.
Route::get('regulamento', [RegulationController::class, 'show'])->name('regulamento.show');
Route::get('regulamento/download', [RegulationController::class, 'download'])->name('regulamento.download');

// Destino do link "Saiba quais" do aviso de cookies (issue #73).
Route::get('cookies', [CookiePolicyController::class, 'show'])->name('cookies.show');

// LGPD exige acesso fácil, sem login (issue #78).
Route::get('privacidade', [PrivacyPolicyController::class, 'show'])->name('privacidade.show');

// Resultado público. Só mostra algo se results_published_at não for nulo
// -- checado no servidor, ver Public\ResultController. Sem slug: edição
// atual. Com slug: uma edição específica (issue #97).
Route::get('resultados', [PublicResultController::class, 'show'])->name('resultados.show');
Route::get('resultados/{event:slug}', [PublicResultController::class, 'show'])->name('resultados.show.edicao');

// Lista as edições encerradas com resultado publicado (issue #97).
Route::get('edicoes', [EditionController::class, 'index'])->name('edicoes.index');

// Vitrine dos projetos enviados. É daqui que o voto popular acontece --
// quem pode votar é decidido no servidor (PopularVotePolicy), não aqui.
Route::get('projetos', [SubmissionShowcaseController::class, 'index'])->name('projetos.index');

// Validação pública de certificado. whereUuid: código mal formado devolve
// 404 de rota, não erro de sintaxe do Postgres -- mesmo motivo do
// checkin/{user:qr_token} (ver comentário lá embaixo).
Route::get('validar/{code}', [CertificateValidationController::class, 'show'])
    ->name('certificates.validate')
    ->whereUuid('code');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [ParticipantDashboardController::class, 'index'])->name('dashboard');
});

// Inscricao no evento. 'verified' porque e-mail nao confirmado nao pode
// virar membro de equipe -- ver EventPolicy::register.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('inscricao', [EventRegistrationController::class, 'create'])
        ->name('registration.create');

    Route::post('inscricao', [EventRegistrationController::class, 'store'])
        ->name('registration.store');

    // Crachá digital. Qualquer autenticado -- jurado e organizador também
    // passam por checkpoint no dia do evento, não é só participante.
    Route::get('credencial', [CredentialController::class, 'show'])->name('credencial.show');

    Route::get('equipe', [TeamController::class, 'show'])->name('teams.show');
    Route::get('equipe/criar', [TeamController::class, 'create'])->name('teams.create');
    Route::post('equipe', [TeamController::class, 'store'])->name('teams.store');

    Route::get('equipe/entrar', [TeamMembershipController::class, 'create'])->name('teams.join.create');
    Route::post('equipe/entrar', [TeamMembershipController::class, 'store'])->name('teams.join.store');

    Route::delete('equipe/membros/{membership}', [TeamMembershipController::class, 'destroy'])
        ->name('teams.leave');
    Route::delete('equipe/membros/{membership}/remover', [TeamMembershipController::class, 'remove'])
        ->name('teams.members.remove');
    Route::patch('equipe/{team}/lideranca', [TeamLeadershipController::class, 'update'])
        ->name('teams.leadership.update');

    // Convite por e-mail. 'aceitar' fica fora de /equipe porque o link vem
    // de fora (e-mail) e o token já identifica o convite -- ver
    // TeamInviteController.
    Route::post('equipe/convites', [TeamInviteController::class, 'store'])->name('team-invites.store');
    Route::get('convites/{invite:token}/aceitar', [TeamInviteController::class, 'accept'])->name('team-invites.accept');

    // Submissao do projeto. Salvar rascunho e enviar sao rotas separadas
    // porque as exigencias sao outras: rascunho aceita campo em branco, o
    // envio nao -- ver SaveSubmissionRequest e SubmitSubmissionRequest.
    Route::get('submissao', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::post('submissao', [SubmissionController::class, 'save'])->name('submissions.save');
    Route::post('submissao/enviar', [SubmissionController::class, 'submit'])->name('submissions.submit');

    // Arquivos da submissao. O download passa por rota autorizada porque o
    // storage fica fora do webroot -- nunca ha link direto pro disco.
    Route::post('submissao/arquivos', [SubmissionFileController::class, 'store'])
        ->name('submission-files.store');
    Route::get('submissao/arquivos/{file}', [SubmissionFileController::class, 'download'])
        ->name('submission-files.download');
    Route::delete('submissao/arquivos/{file}', [SubmissionFileController::class, 'destroy'])
        ->name('submission-files.destroy');

    // Destino do QR do crachá. Fora do prefixo /admin de propósito: é a URL
    // que a câmera do celular abre direto, igual o crachá promete -- ver
    // Support\CheckinQrCode. AttendancePolicy barra quem não é staff.
    //
    // whereUuid(): qr_token é coluna uuid no Postgres. Sem restringir o
    // formato aqui, um token mal formado ("abc") chega cru na query e o
    // Postgres estoura erro de sintaxe -- 500 em vez do 404 limpo que um
    // token só inválido merece.
    Route::get('checkin/{user:qr_token}', [CheckinController::class, 'show'])->name('checkin.show')->whereUuid('user');
    Route::post('checkin/{user:qr_token}', [CheckinController::class, 'store'])->name('checkin.store')->whereUuid('user');

    // Painel do jurado. Autorização em cada método -- ver
    // Judge\EvaluationController e EvaluationPolicy.
    Route::get('jurado', [EvaluationController::class, 'index'])->name('jurado.index');
    Route::get('jurado/avaliar/{submission}', [EvaluationController::class, 'show'])->name('jurado.avaliar.show');
    Route::post('jurado/avaliar/{submission}/rascunho', [EvaluationController::class, 'autosave'])->name('jurado.avaliar.autosave');
    Route::post('jurado/avaliar/{submission}/enviar', [EvaluationController::class, 'submit'])->name('jurado.avaliar.enviar');

    // Voto popular. Autorização (inscrito + dentro da janela) é da
    // PopularVotePolicy -- ver Participant\PopularVoteController.
    Route::post('votos', [PopularVoteController::class, 'store'])->name('votos.store');

    // Certificados da própria pessoa. Sem Policy no index -- a query já
    // filtra por request()->user(), igual credencial.show. O download passa
    // por CertificatePolicy porque a rota recebe {certificate} de fora.
    Route::get('certificados', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('certificados/{certificate}/baixar', [CertificateController::class, 'download'])->name('certificates.download');
});

// Painel do organizador. A porta é a Policy, não o prefixo da URL: `can:` na
// rota garante que ninguém chega ao controller sem passar por
// SubmissionPolicy::viewAny.
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    // Criar a primeira edição do evento e gerenciar papel de usuário não
    // dependem de já existir um evento em cartaz -- ficam fora do grupo com
    // EnsureEventExists abaixo, senão a própria tela de criar o evento
    // ficaria bloqueada por "não existe evento".
    Route::get('evento/criar', [EventController::class, 'create'])->name('admin.evento.create');
    Route::post('evento', [EventController::class, 'store'])->name('admin.evento.store');

    // Gerenciar papel de usuário -- exclusivo de admin, ver UserPolicy.
    Route::get('usuarios', [OrganizerUserController::class, 'index'])->name('admin.usuarios.index');
    Route::patch('usuarios/{usuario}', [OrganizerUserController::class, 'update'])->name('admin.usuarios.update');
});

// Todo o resto do painel do organizador pressupõe uma edição do hackathon
// em cartaz -- ver EnsureEventExists. A porta em cada tela específica é a
// Policy, não o prefixo da URL: `can:` na rota garante que ninguém chega ao
// controller sem passar por SubmissionPolicy::viewAny, por exemplo.
Route::middleware(['auth', 'verified', EnsureEventExists::class])->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Edição do evento -- nome, tema/desafio, datas, limites, fase. Sempre o
    // evento atual, sem {event} na URL: só existe uma edição em cartaz por
    // vez (PLANO.md, seção 5).
    Route::get('evento', [EventController::class, 'edit'])->name('admin.evento.edit');
    Route::patch('evento', [EventController::class, 'update'])->name('admin.evento.update');
    Route::post('evento/regulamento', [EventController::class, 'uploadRegulation'])->name('admin.evento.regulamento.upload');

    // Incidentes do dia do evento. Extensão de prazo vale pra todo mundo --
    // ver IncidentController e Event::effectiveSubmissionDeadline().
    Route::get('incidentes', [IncidentController::class, 'index'])->name('admin.incidentes.index');
    Route::post('incidentes', [IncidentController::class, 'store'])->name('admin.incidentes.store');

    Route::get('submissoes', [OrganizerSubmissionController::class, 'index'])
        ->can('viewAny', Submission::class)
        ->name('admin.submissions.index');

    // Antes de '{submission}': senão o binding implícito tenta achar uma
    // submissão com id "exportar" e devolve 404 no lugar do zip.
    Route::get('submissoes/exportar', [OrganizerSubmissionController::class, 'export'])
        ->can('viewAny', Submission::class)
        ->name('admin.submissions.export');

    // Lançamento manual (plano B, degraus 3 e 4) -- mesmo motivo do
    // 'exportar' acima, precisa vir antes de '{submission}'.
    Route::get('submissoes/lancar', [ManualSubmissionController::class, 'create'])->name('admin.submissions.record.create');
    Route::post('submissoes/lancar', [ManualSubmissionController::class, 'store'])->name('admin.submissions.record.store');

    Route::get('submissoes/{submission}', [OrganizerSubmissionController::class, 'show'])
        ->can('view', 'submission')
        ->name('admin.submissions.show');

    // CRUD da agenda. Autorização em cada método do controller, não aqui --
    // ver ScheduleItemController.
    Route::get('agenda', [ScheduleItemController::class, 'index'])->name('admin.agenda.index');
    Route::get('agenda/criar', [ScheduleItemController::class, 'create'])->name('admin.agenda.create');
    Route::post('agenda', [ScheduleItemController::class, 'store'])->name('admin.agenda.store');
    Route::get('agenda/{item}/editar', [ScheduleItemController::class, 'edit'])->name('admin.agenda.edit');
    Route::patch('agenda/{item}', [ScheduleItemController::class, 'update'])->name('admin.agenda.update');
    Route::patch('agenda/{item}/publicar', [ScheduleItemController::class, 'publish'])->name('admin.agenda.publish');
    Route::delete('agenda/{item}', [ScheduleItemController::class, 'destroy'])->name('admin.agenda.destroy');

    // Busca manual do check-in -- fallback de quando o crachá não tem como
    // ser lido (PLANO.md, Anexo A).
    Route::get('checkin', [CheckinController::class, 'index'])->name('admin.checkin.index');
    Route::post('checkin/checkpoints', [CheckinController::class, 'storeCheckpoint'])->name('admin.checkin.checkpoints.store');

    // CRUD da rubrica. Autorização em cada método -- ver RubricController.
    Route::get('rubrica', [OrganizerRubricController::class, 'index'])->name('admin.rubrica.index');
    Route::post('rubrica', [OrganizerRubricController::class, 'store'])->name('admin.rubrica.store');
    Route::get('rubrica/{rubric}', [OrganizerRubricController::class, 'show'])->name('admin.rubrica.show');
    Route::patch('rubrica/{rubric}/ativar', [OrganizerRubricController::class, 'activate'])->name('admin.rubrica.activate');
    Route::delete('rubrica/{rubric}', [OrganizerRubricController::class, 'destroy'])->name('admin.rubrica.destroy');

    Route::post('rubrica/{rubric}/criterios', [OrganizerRubricController::class, 'storeCriterion'])->name('admin.rubrica.criteria.store');
    Route::patch('criterios/{criterion}', [OrganizerRubricController::class, 'updateCriterion'])->name('admin.rubrica.criteria.update');
    Route::delete('criterios/{criterion}', [OrganizerRubricController::class, 'destroyCriterion'])->name('admin.rubrica.criteria.destroy');

    // Atribuição de jurados. Autorização em cada método -- ver JudgeAssignmentController.
    Route::get('jurados', [JudgeAssignmentController::class, 'index'])->name('admin.jurados.index');
    Route::post('jurados/distribuir', [JudgeAssignmentController::class, 'distribute'])->name('admin.jurados.distribute');
    Route::post('jurados', [JudgeAssignmentController::class, 'store'])->name('admin.jurados.store');
    Route::delete('jurados/{assignment}', [JudgeAssignmentController::class, 'destroy'])->name('admin.jurados.destroy');
    Route::post('jurados/{assignment}/reatribuir', [JudgeAssignmentController::class, 'reassign'])->name('admin.jurados.reassign');
    Route::post('jurados/{assignment}/reabrir-avaliacao', [JudgeAssignmentController::class, 'reopenEvaluation'])->name('admin.jurados.reopen-evaluation');
    Route::patch('jurados/configuracao', [JudgeAssignmentController::class, 'updateJudgesPerSubmission'])->name('admin.jurados.config');

    Route::post('jurados/conflitos', [JudgeAssignmentController::class, 'storeConflict'])->name('admin.jurados.conflicts.store');
    Route::delete('jurados/conflitos/{conflict}', [JudgeAssignmentController::class, 'destroyConflict'])->name('admin.jurados.conflicts.destroy');

    // Resultados. Calcular nunca publica sozinho -- ver ResultController.
    Route::get('resultados', [ResultController::class, 'index'])->name('admin.resultados.index');
    Route::post('resultados/recalcular', [ResultController::class, 'recompute'])->name('admin.resultados.recompute');
    Route::post('resultados/publicar', [ResultController::class, 'publish'])->name('admin.resultados.publish');

    // Certificados. Emissão em lote é o comando hackathon:issue-certificates;
    // aqui só a emissão avulsa (mentor, correção pontual) -- ver CertificateController.
    Route::get('certificados', [OrganizerCertificateController::class, 'index'])->name('admin.certificados.index');
    Route::post('certificados', [OrganizerCertificateController::class, 'store'])->name('admin.certificados.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
