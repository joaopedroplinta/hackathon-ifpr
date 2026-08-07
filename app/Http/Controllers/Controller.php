<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * O Laravel 11+ não traz mais AuthorizesRequests no controller base. Como
 * autorização aqui é obrigatória em toda ação (ver .claude/rules/security.md),
 * o trait fica na base em vez de repetido em cada controller.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
