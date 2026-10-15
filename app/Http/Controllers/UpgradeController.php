<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\UserHardware;
use App\Services\HardwareScorer;
use App\Services\Upgrades\UpgradeAdvisor;
use App\Services\Upgrades\UpgradeSummary;
use Illuminate\Http\Request;

/** "What should I upgrade?" for the visitor's active PC, optionally for one game. */
class UpgradeController extends Controller
{
    public function __invoke(Request $request, UpgradeAdvisor $advisor, UpgradeSummary $summary, HardwareScorer $scorer)
    {
        $pc = UserHardware::activeForVisitor();
        if (! $pc) {
            return redirect()->route('hardware.add-specs')
                ->with('success', 'Add your PC first, then we can tell you what to upgrade.');
        }

        $game = $request->filled('game') ? App::where('slug', (string) $request->query('game'))->first() : null;
        $advice = $advisor->advise($pc, $game);

        return view('upgrade.show', [
            'pc' => $pc,
            'tier' => $scorer->getHardwareTier((float) $pc->overall_score),
            'advice' => $advice,
            'summary' => $summary->write($pc, $advice),
        ]);
    }
}
