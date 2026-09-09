<?php

namespace Tests\Feature;

use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class MailTemplateTest extends TestCase
{
    public function test_the_html_template_uses_the_current_brand_with_accessible_contrast(): void
    {
        $html = $this->renderMessage();

        $this->assertStringContainsString('class="logo-mark"', $html);
        $this->assertStringContainsString('IFPR Campus Pinhais', $html);
        $this->assertStringContainsString('color: #ffffff', $html);
        $this->assertStringNotContainsString('&gt;_', $html);
        $this->assertStringNotContainsString('>_', $html);
    }

    public function test_the_header_uses_email_safe_links_instead_of_wrapping_a_table(): void
    {
        $html = $this->renderMessage();

        $this->assertDoesNotMatchRegularExpression('/<a[^>]*>\s*<table/i', $html);
        $this->assertMatchesRegularExpression('/<a[^>]*class="logo-link"[^>]*><span/i', $html);
        $this->assertStringContainsString('class="header" align="center" width="600"', $html);
        $this->assertStringContainsString('class="inner-body" align="center" width="600"', $html);
    }

    public function test_the_primary_action_is_left_aligned_and_keeps_the_fallback_link(): void
    {
        $html = $this->renderMessage();

        $this->assertStringContainsString('class="action" align="left"', $html);
        $this->assertStringContainsString('Confirmar e-mail', $html);
        $this->assertStringContainsString('copie e cole a URL abaixo', $html);
    }

    public function test_the_plain_text_template_keeps_the_institutional_identity(): void
    {
        $message = $this->makeMessage();
        $text = app(Markdown::class)->renderText($message->markdown, $message->data())->toHtml();

        $this->assertStringContainsString('Hackathon IFPR — IFPR Campus Pinhais', $text);
        $this->assertStringContainsString('Confirmar e-mail: https://hackathon.example/confirmar', $text);
    }

    private function renderMessage(): string
    {
        return $this->makeMessage()
            ->render()
            ->toHtml();
    }

    private function makeMessage(): MailMessage
    {
        return (new MailMessage)
            ->greeting('Olá!')
            ->line('Confirme seu e-mail para acessar o Hackathon IFPR.')
            ->action('Confirmar e-mail', 'https://hackathon.example/confirmar');
    }
}
