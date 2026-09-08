<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\ResetPasswordQueued;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResetPasswordQueuedTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_dispatched_through_the_queue(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new ResetPasswordQueued('token-de-teste'));
    }

    public function test_mail_message_is_entirely_in_portuguese(): void
    {
        $mail = (new ResetPasswordQueued('token-de-teste'))->toMail(User::factory()->create());

        $this->assertSame('Redefina sua senha — Hackathon IFPR', $mail->subject);
        $this->assertSame('Redefinir senha', $mail->actionText);
        $this->assertStringNotContainsString('Reset Password', implode(' ', $mail->introLines));
        $this->assertStringNotContainsString('You are receiving', implode(' ', $mail->introLines));
    }
}
