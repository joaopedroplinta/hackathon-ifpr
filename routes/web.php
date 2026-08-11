<?php

use App\Http\Controllers\Judge\EvaluationController;
use App\Http\Controllers\Organizer\CheckinController;
use App\Http\Controllers\Organizer\JudgeAssignmentController;
use App\Http\Controllers\Organizer\ResultController;
use App\Http\Controllers\Organizer\RubricController as OrganizerRubricController;
use App\Http\Controllers\Organizer\ScheduleItemController;
use App\Http\Controllers\Organizer\SubmissionController as OrganizerSubmissionController;
use App\Http\Controllers\Participant\CredentialController;
use App\Http\Controllers\Participant\EventRegistrationController;
use App\Http\Controllers\Participant\SubmissionController;
use App\Http\Controllers\Participant\SubmissionFileController;
use App\Http\Controllers\Participant\TeamController;
use App\Http\Controllers\Participant\TeamInviteController;
use App\Http\Controllers\Participant\TeamLeadershipController;
use App\Http\Controllers\Participant\TeamMembershipController;
use App\Http\Controllers\Public\AgendaController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\ResultController as PublicResultController;
use App\Http\Controllers\Public\RubricController as PublicRubricController;
use App\Models\Submission;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [LandingController::class, 'show'])->name('home');

// Agenda pública. Sem 'auth': é o que a tabela de papéis promete pro guest
// (PLANO.md, seção 3) -- ver agenda de novo/oficinas antes mesmo de se inscrever.
Route::get('agenda', [AgendaController::class, 'index'])->name('agenda.index');
Route::get('agenda.ics', [AgendaController::class, 'ics'])->name('agenda.ics');

// Rubrica pública -- .claude/rules do Anexo A: reduz disputa sobre nota
// deixar os critérios visíveis desde antes do evento.
Route::get('rubrica', [PublicRubricController::class, 'show'])->name('rubrica.show');

// Resultado público. Só mostra algo se results_published_at não for nulo
// -- checado no servidor, ver Public\ResultController.
Route::get('resultados', [PublicResultController::class, 'show'])->name('resultados.show');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
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
});

// Painel do organizador. A porta é a Policy, não o prefixo da URL: `can:` na
// rota garante que ninguém chega ao controller sem passar por
// SubmissionPolicy::viewAny.
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('submissoes', [OrganizerSubmissionController::class, 'index'])
        ->can('viewAny', Submission::class)
        ->name('admin.submissions.index');

    // Antes de '{submission}': senão o binding implícito tenta achar uma
    // submissão com id "exportar" e devolve 404 no lugar do zip.
    Route::get('submissoes/exportar', [OrganizerSubmissionController::class, 'export'])
        ->can('viewAny', Submission::class)
        ->name('admin.submissions.export');

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
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
