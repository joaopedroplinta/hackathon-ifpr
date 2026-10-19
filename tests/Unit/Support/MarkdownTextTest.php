<?php

namespace Tests\Unit\Support;

use App\Support\MarkdownText;
use PHPUnit\Framework\TestCase;

class MarkdownTextTest extends TestCase
{
    public function test_it_escapes_markdown_metacharacters(): void
    {
        $this->assertSame('\[clique\]\(https://x\.example\)', MarkdownText::escape('[clique](https://x.example)'));
        $this->assertSame('\*\*forte\*\* \_e\_ \`code\`', MarkdownText::escape('**forte** _e_ `code`'));
    }

    public function test_plain_names_are_unchanged(): void
    {
        $this->assertSame('Equipe Alfa 2', MarkdownText::escape('Equipe Alfa 2'));
    }
}
