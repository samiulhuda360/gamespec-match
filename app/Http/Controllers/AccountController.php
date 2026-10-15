<?php

namespace App\Http\Controllers;

use App\Models\UserHardware;
use App\Services\HardwareScorer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __invoke(Request $request, HardwareScorer $scorer): View
    {
        $active = UserHardware::activeForVisitor();
        $pcs = UserHardware::where('user_id', $request->user()->id)->latest('updated_at')->get()
            ->each(fn (UserHardware $pc) => $pc->tier = $scorer->getHardwareTier((float) $pc->overall_score));

        return view('account.index', [
            'user' => $request->user(),
            'pcs' => $pcs,
            'activeId' => $active?->id,
        ]);
    }
}
