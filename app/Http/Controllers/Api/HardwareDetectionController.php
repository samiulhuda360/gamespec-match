<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HardwareDetection;
use App\Models\GPU;
use App\Models\CPU;
use Illuminate\Http\Request;

class HardwareDetectionController extends Controller
{
    /**
     * Save hardware detection data for analysis
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'gpu_raw' => 'nullable|string|max:500',
            'cpu_raw' => 'nullable|string|max:255',
            'cpu_cores' => 'nullable|integer',
            'cpu_threads' => 'nullable|integer',
            'ram_gb' => 'nullable|integer',
            'platform' => 'nullable|string|max:50',
            'detection_method' => 'nullable|string|max:50',
        ]);

        // Check if this exact detection already exists (within last 30 days)
        $existing = HardwareDetection::where('gpu_raw', $validated['gpu_raw'] ?? null)
            ->where('cpu_raw', $validated['cpu_raw'] ?? null)
            ->where('ip_address', $request->ip())
            ->where('created_at', '>', now()->subDays(30))
            ->first();

        if ($existing) {
            // Just increment count
            $existing->increment('detection_count');
            return response()->json([
                'success' => true,
                'message' => 'Detection recorded',
                'detection_id' => $existing->id
            ]);
        }

        // Try to match GPU in database
        $matchedGPU = null;
        $gpuMatched = false;
        if (!empty($validated['gpu_raw'])) {
            $matchedGPU = $this->findMatchingGPU($validated['gpu_raw']);
            $gpuMatched = $matchedGPU !== null;
        }

        // Try to match CPU in database
        $matchedCPU = null;
        $cpuMatched = false;
        if (!empty($validated['cpu_raw']) || !empty($validated['cpu_threads'])) {
            $matchedCPU = $this->findMatchingCPU($validated['cpu_raw'] ?? '', $validated['cpu_threads'] ?? null);
            $cpuMatched = $matchedCPU !== null;
        }

        // Create new detection record
        $detection = HardwareDetection::create([
            'gpu_raw' => $validated['gpu_raw'] ?? null,
            'cpu_raw' => $validated['cpu_raw'] ?? null,
            'cpu_cores' => $validated['cpu_cores'] ?? null,
            'cpu_threads' => $validated['cpu_threads'] ?? null,
            'ram_gb' => $validated['ram_gb'] ?? null,
            'platform' => $validated['platform'] ?? null,
            'user_agent' => $request->userAgent(),
            'matched_gpu_id' => $matchedGPU?->id,
            'matched_cpu_id' => $matchedCPU?->id,
            'gpu_matched' => $gpuMatched,
            'cpu_matched' => $cpuMatched,
            'detection_method' => $validated['detection_method'] ?? 'webgl',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detection saved',
            'detection_id' => $detection->id,
            'matched' => [
                'gpu' => $gpuMatched ? $matchedGPU->name : null,
                'cpu' => $cpuMatched ? $matchedCPU->name : null,
            ]
        ]);
    }

    /**
     * Try to find matching GPU in database
     */
    private function findMatchingGPU(string $gpuRaw)
    {
        // Clean up the GPU string
        $cleaned = strtolower($gpuRaw);

        // Extract key parts (brand, series, model)
        $brands = ['nvidia', 'amd', 'intel'];
        $brand = null;
        foreach ($brands as $b) {
            if (str_contains($cleaned, $b)) {
                $brand = $b;
                break;
            }
        }

        if (!$brand) {
            return null;
        }

        // Try fuzzy matching
        return GPU::where('manufacturer', $brand)
            ->get()
            ->first(function($gpu) use ($cleaned) {
                $gpuName = strtolower($gpu->name);
                // Extract numbers from both strings
                preg_match_all('/\d+/', $cleaned, $rawNumbers);
                preg_match_all('/\d+/', $gpuName, $dbNumbers);

                // If they share significant numbers, likely a match
                $commonNumbers = array_intersect($rawNumbers[0] ?? [], $dbNumbers[0] ?? []);
                return count($commonNumbers) >= 2; // At least 2 common numbers (e.g., "6900" and "XT")
            });
    }

    /**
     * Try to find matching CPU in database
     */
    private function findMatchingCPU(string $cpuRaw, ?int $threads)
    {
        if (empty($cpuRaw) && empty($threads)) {
            return null;
        }

        // If we have thread count, use that for matching
        if ($threads) {
            return CPU::where('threads', $threads)->first();
        }

        // Otherwise try string matching
        $cleaned = strtolower($cpuRaw);

        // Extract brand
        $brands = ['intel', 'amd'];
        $brand = null;
        foreach ($brands as $b) {
            if (str_contains($cleaned, $b)) {
                $brand = $b;
                break;
            }
        }

        if (!$brand) {
            return null;
        }

        return CPU::where('manufacturer', $brand)
            ->get()
            ->first(function($cpu) use ($cleaned) {
                return str_contains($cleaned, strtolower($cpu->name));
            });
    }

    /**
     * Get unmatched hardware detections (admin only)
     */
    public function unmatchedStats()
    {
        return response()->json([
            'unmatched_gpus' => HardwareDetection::getUnmatchedGPUs()->take(20),
            'unmatched_cpus' => HardwareDetection::getUnmatchedCPUs()->take(20),
            'total_detections' => HardwareDetection::count(),
            'match_rate' => [
                'gpu' => HardwareDetection::whereNotNull('gpu_raw')->count() > 0
                    ? round(HardwareDetection::where('gpu_matched', true)->count() / HardwareDetection::whereNotNull('gpu_raw')->count() * 100, 2)
                    : 0,
                'cpu' => HardwareDetection::whereNotNull('cpu_raw')->count() > 0
                    ? round(HardwareDetection::where('cpu_matched', true)->count() / HardwareDetection::whereNotNull('cpu_raw')->count() * 100, 2)
                    : 0,
            ]
        ]);
    }
}
