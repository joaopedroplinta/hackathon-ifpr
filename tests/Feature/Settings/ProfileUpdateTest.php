<?php

namespace Tests\Feature\Settings;

use App\Models\Certificate;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/settings/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_cpf_with_valid_checksum_is_saved_without_mask(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'cpf' => '529.982.287-12',
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/settings/profile');
        $this->assertSame('52998228712', $user->fresh()->cpf);
    }

    public function test_cpf_with_wrong_check_digit_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/profile')
            ->patch('/settings/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'cpf' => '529.982.287-13',
            ]);

        $response->assertSessionHasErrors('cpf');
        $this->assertNull($user->fresh()->cpf);
    }

    public function test_cpf_already_used_by_another_account_is_rejected(): void
    {
        User::factory()->create(['cpf' => '52998228712']);
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/profile')
            ->patch('/settings/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'cpf' => '529.982.287-12',
            ]);

        $response->assertSessionHasErrors('cpf');
    }

    public function test_vinculo_aluno_ifpr_requires_matricula_suap(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/profile')
            ->patch('/settings/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'tipo_vinculo' => 'aluno_ifpr',
            ]);

        $response->assertSessionHasErrors('matricula_suap');
    }

    public function test_vinculo_and_matricula_are_saved_together(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'tipo_vinculo' => 'aluno_ifpr',
                'matricula_suap' => '2024104070001',
            ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('aluno_ifpr', $user->tipo_vinculo->value);
        $this->assertSame('2024104070001', $user->matricula_suap);
    }

    /**
     * Trocar de vínculo não pode deixar uma matrícula do vínculo anterior
     * esquecida no banco -- quem era aluno e virou externo não continua
     * com um SUAP desatualizado guardado.
     */
    public function test_switching_vinculo_clears_the_previous_matricula(): void
    {
        $user = User::factory()->create([
            'tipo_vinculo' => 'aluno_ifpr',
            'matricula_suap' => '2024104070001',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'tipo_vinculo' => 'externo',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->matricula_suap);
    }

    public function test_avatar_can_be_uploaded_and_replaces_the_previous_local_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $primeira = $this->actingAs($user)->post('/settings/profile/foto', [
            'foto' => UploadedFile::fake()->image('foto.jpg', 200, 200),
        ]);
        $primeira->assertSessionHasNoErrors()->assertRedirect('/settings/profile');

        $caminhoAntigo = str_replace(Storage::disk('public')->url(''), '', $user->fresh()->avatar_url);
        Storage::disk('public')->assertExists($caminhoAntigo);

        $segunda = $this->actingAs($user)->post('/settings/profile/foto', [
            'foto' => UploadedFile::fake()->image('nova.jpg', 200, 200),
        ]);
        $segunda->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($caminhoAntigo);
    }

    public function test_avatar_upload_rejects_a_file_that_is_not_an_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/profile')
            ->post('/settings/profile/foto', [
                'foto' => UploadedFile::fake()->create('curriculo.pdf', 100),
            ]);

        $response->assertSessionHasErrors('foto');
        $this->assertNull($user->fresh()->avatar_url);
    }

    public function test_avatar_can_be_removed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user)->post('/settings/profile/foto', [
            'foto' => UploadedFile::fake()->image('foto.jpg', 200, 200),
        ]);
        $caminho = str_replace(Storage::disk('public')->url(''), '', $user->fresh()->avatar_url);

        $response = $this->actingAs($user)->delete('/settings/profile/foto');

        $response->assertSessionHasNoErrors()->assertRedirect('/settings/profile');
        $this->assertNull($user->fresh()->avatar_url);
        Storage::disk('public')->assertMissing($caminho);
    }

    /**
     * Uma foto vinda do Google é URL externa (accounts.google.com/...),
     * nunca um caminho em storage/app/public -- remover não pode tentar
     * apagar arquivo nenhum do nosso disco nesse caso.
     */
    public function test_removing_a_google_avatar_does_not_touch_local_disk(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['avatar_url' => 'https://lh3.googleusercontent.com/a/foto123']);

        $response = $this->actingAs($user)->delete('/settings/profile/foto');

        $response->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->avatar_url);
    }

    /**
     * "Anonimizado" que deixa CPF ou a foto do rosto da pessoa intactos não
     * anonimizou nada de verdade -- os dois identificam tanto quanto
     * nome/e-mail.
     */
    public function test_deleting_the_account_clears_cpf_matricula_and_local_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'cpf' => '52998228712',
            'tipo_vinculo' => 'aluno_ifpr',
            'matricula_suap' => '2024104070001',
        ]);
        $this->actingAs($user)->post('/settings/profile/foto', [
            'foto' => UploadedFile::fake()->image('foto.jpg', 200, 200),
        ]);
        $caminhoFoto = str_replace(Storage::disk('public')->url(''), '', $user->fresh()->avatar_url);

        $response = $this->actingAs($user)->delete('/settings/profile', ['password' => 'password']);

        $response->assertSessionHasNoErrors()->assertRedirect('/');

        $anonimizado = User::withTrashed()->find($user->id);
        $this->assertNull($anonimizado->cpf);
        $this->assertNull($anonimizado->tipo_vinculo);
        $this->assertNull($anonimizado->matricula_suap);
        $this->assertNull($anonimizado->avatar_url);
        Storage::disk('public')->assertMissing($caminhoFoto);
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/settings/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();

        // fresh() ignora o soft delete por padrão -- find() é quem reflete
        // o que toda consulta normal (login, listagem) de fato enxerga.
        $this->assertNull(User::find($user->id));
        $this->assertTrue(User::withTrashed()->find($user->id)->trashed());
    }

    public function test_correct_password_must_be_provided_to_delete_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/profile')
            ->delete('/settings/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->fresh());
    }

    /**
     * certificates.user_id é restrictOnDelete() de propósito (registro
     * histórico) -- sem soft delete + anonimização, isso quebrava com
     * violação de foreign key pra qualquer pessoa com certificado emitido.
     */
    public function test_deleting_the_account_does_not_break_for_a_user_with_a_certificate(): void
    {
        $user = User::factory()->create();
        $registration = EventRegistration::factory()->for($user)->create([
            'phone' => '(41) 90000-0000',
            'course' => 'Análise e Desenvolvimento de Sistemas',
        ]);
        $certificate = Certificate::factory()->for($user)->create();

        $response = $this
            ->actingAs($user)
            ->delete('/settings/profile', ['password' => 'password']);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull(User::find($user->id));

        $user = User::withTrashed()->find($user->id);
        $this->assertSame('Usuário removido', $user->name);
        $this->assertSame("removido-{$user->id}@removido.local", $user->email);
        $this->assertTrue($user->trashed());

        $registration->refresh();
        $this->assertNull($registration->phone);
        $this->assertNull($registration->course);

        // O registro histórico continua existindo, só a pessoa some.
        $this->assertNotNull($certificate->fresh());
    }
}
