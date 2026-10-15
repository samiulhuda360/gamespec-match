<?php

namespace App\Http\Controllers;

use App\Models\UserHardware;
use App\Services\Search\GameSearch;
use App\Services\Search\QueryInterpreter;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** "Ask GameSpec": search the catalogue in plain words. */
class AskController extends Controller
{
    public const EXAMPLES = [
        'co-op survival games under $20',
        'relaxing farming game like Stardew Valley',
        'story-rich RPG my PC can run at high settings',
        'free multiplayer shooters with good reviews',
        'split-screen games to play with my kids',
        'strategy games released after 2020 with no microtransactions',
    ];

    public function __invoke(Request $request, QueryInterpreter $interpreter, GameSearch $search): View
    {
        $request->validate(['q' => 'nullable|string|max:'.QueryInterpreter::MAX_CHARS]);
        $text = trim((string) $request->query('q', ''));
        $pc = UserHardware::activeForVisitor();

        $query = $text !== '' ? $interpreter->interpret($text) : null;

        return view('ask.index', [
            'text' => $text,
            'query' => $query,
            'result' => $query ? $search->run($query, $pc) : null,
            'pc' => $pc,
            'examples' => self::EXAMPLES,
        ]);
    }
}
