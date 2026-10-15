<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\CPU;
use App\Models\GPU;
use App\Models\UserHardware;
use App\Services\CompatibilityChecker;
use App\Services\HardwareScorer;
use App\Services\Specs\SpecReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HardwareController extends Controller
{
    public function __construct(
        private HardwareScorer $scorer,
        private CompatibilityChecker $checker
    ) {}

    public function addSpecs()
    {
        // Cache CPU/GPU data for 1 hour to improve performance
        $cpus = Cache::remember('hardware_cpus_grouped', 3600, fn () => CPU::getAllGrouped());
        $gpus = Cache::remember('hardware_gpus_grouped', 3600, fn () => GPU::getAllGrouped());

        $ramOptions = [
            4 => '4 GB', 8 => '8 GB', 12 => '12 GB', 16 => '16 GB', 24 => '24 GB',
            32 => '32 GB', 48 => '48 GB', 64 => '64 GB', 96 => '96 GB', 128 => '128 GB',
        ];

        $platformOptions = [
            'windows_10' => 'Windows 10',
            'windows_11' => 'Windows 11',
            'macos' => 'macOS',
            'linux' => 'Linux',
        ];

        $existingHardware = UserHardware::activeForVisitor();
        $compatibleGames = collect();

        if ($existingHardware) {
            // Load compatible games with pagination (50 per page for better performance)
            $compatibleGames = $this->checker->findCompatibleGames($existingHardware, 50, true);

            $compatibleGames->load([
                'sysRequirements',
                'pricing' => fn ($query) => $query->where('currency', 'USD'),
                'reviews',
            ]);
        }

        return view('hardware.add-specs', compact(
            'cpus',
            'gpus',
            'ramOptions',
            'platformOptions',
            'existingHardware',
            'compatibleGames'
        ));
    }

    /** Editing happens on the add-specs page, which edits the active profile. */
    public function edit($id)
    {
        $hardware = $this->owned($id);
        session()->put('active_hardware_id', $hardware->id);

        return redirect()->route('hardware.add-specs');
    }

    public function update(Request $request, $id)
    {
        $hardware = $this->owned($id);
        $this->fillFromRequest($hardware, $request);
        $this->scorer->scoreHardware($hardware);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'PC specs updated successfully!',
                'hardware_id' => $hardware->id,
            ]);
        }

        return redirect()
            ->route('hardware.show', $hardware->id)
            ->with('success', 'PC specs updated successfully!');
    }

    public function store(Request $request)
    {
        // Updates the visitor's active profile, or creates one.
        $hardware = UserHardware::activeForVisitor() ?? new UserHardware();
        if (! $hardware->exists) {
            if (auth()->check()) {
                $hardware->user_id = auth()->id();
            } else {
                $hardware->session_id = session()->getId();
            }
        }

        $this->fillFromRequest($hardware, $request);
        $this->scorer->scoreHardware($hardware);

        session()->put('active_hardware_id', $hardware->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'hardware_id' => $hardware->id,
                'redirect' => route('hardware.add-specs'),
            ]);
        }

        return redirect()
            ->route('hardware.add-specs')
            ->with('success', 'Hardware profile updated successfully!');
    }

    public function show($id)
    {
        $hardware = $this->owned($id);
        // Use pagination for better performance (50 per page)
        $compatibleGames = $this->checker->findCompatibleGames($hardware, 50, true);

        return view('hardware.show', compact('hardware', 'compatibleGames'));
    }

    public function checkGame(Request $request, $appId)
    {
        $hardwareId = $request->input('hardware_id') ?? session()->get('active_hardware_id');

        if (! $hardwareId) {
            return response()->json(['error' => 'No hardware profile found'], 400);
        }

        $hardware = $this->owned($hardwareId);
        $app = App::with('sysRequirements')->findOrFail($appId);

        return response()->json($this->checker->checkCompatibility($hardware, $app));
    }

    /** "Describe your PC": free text in, catalogue parts out (the visitor still confirms and saves). */
    public function describe(Request $request, SpecReader $reader)
    {
        $data = $request->validate([
            'text' => 'required|string|min:3|max:'.SpecReader::MAX_CHARS,
            'ai' => 'sometimes|boolean',
        ]);

        return response()->json($reader->read($data['text'], $request->boolean('ai', true))->toArray());
    }

    /** A profile the current visitor may see or change, or 404 (not 403, so ids can't be probed). */
    private function owned($id): UserHardware
    {
        $hardware = UserHardware::findOrFail($id);
        abort_unless($hardware->isOwnedByVisitor(), 404);

        return $hardware;
    }

    private function fillFromRequest(UserHardware $hardware, Request $request): void
    {
        $validated = $request->validate([
            'profile_name' => 'required|string|max:100',
            'cpu_id' => 'required|exists:cpus,id',
            'gpu_id' => 'required|exists:gpus,id',
            'ram_gb' => 'required|integer|min:2|max:128',
            'platform' => 'required|in:windows_10,windows_11,macos,linux',
        ]);

        // The model names are kept next to the ids for display and for older rows.
        $hardware->profile_name = $validated['profile_name'];
        $hardware->cpu_id = $validated['cpu_id'];
        $hardware->cpu_model = CPU::findOrFail($validated['cpu_id'])->name;
        $hardware->gpu_id = $validated['gpu_id'];
        $hardware->gpu_model = GPU::findOrFail($validated['gpu_id'])->name;
        $hardware->ram_gb = $validated['ram_gb'];
        $hardware->platform = $validated['platform'];
    }
}
