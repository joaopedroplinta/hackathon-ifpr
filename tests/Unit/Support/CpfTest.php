<?php

namespace Tests\Unit\Support;

use App\Support\Cpf;
use Tests\TestCase;

class CpfTest extends TestCase
{
    public function test_formats_eleven_digits_with_dots_and_dash(): void
    {
        $this->assertSame('123.456.789-00', (new Cpf)->format('12345678900'));
    }

    public function test_returns_null_untouched(): void
    {
        $this->assertNull((new Cpf)->format(null));
    }

    public function test_returns_value_untouched_when_not_eleven_digits(): void
    {
        $this->assertSame('123', (new Cpf)->format('123'));
    }
}
