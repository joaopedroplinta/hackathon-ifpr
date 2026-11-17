<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Users\MarkTourSeen;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\MarkTourSeenRequest;
use Illuminate\Http\RedirectResponse;

class TourController extends Controller
{
    public function __invoke(MarkTourSeenRequest $request, MarkTourSeen $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated('tour'));

        return back();
    }
}
