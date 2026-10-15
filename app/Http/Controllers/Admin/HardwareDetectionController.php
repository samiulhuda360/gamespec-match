<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HardwareDetection;
use Illuminate\Http\Request;

class HardwareDetectionController extends Controller
{
    public function index()
    {
        $stats = [
            'total_detections' => HardwareDetection::count(),
            'unique_gpus' => HardwareDetection::whereNotNull('gpu_raw')->distinct('gpu_raw')->count('gpu_raw'),
            'unique_cpus' => HardwareDetection::whereNotNull('cpu_raw')->distinct('cpu_raw')->count('cpu_raw'),
            'gpu_match_rate' => HardwareDetection::whereNotNull('gpu_raw')->count() > 0
                ? round(HardwareDetection::where('gpu_matched', true)->count() / HardwareDetection::whereNotNull('gpu_raw')->count() * 100, 1)
                : 0,
            'cpu_match_rate' => HardwareDetection::whereNotNull('cpu_raw')->count() > 0
                ? round(HardwareDetection::where('cpu_matched', true)->count() / HardwareDetection::whereNotNull('cpu_raw')->count() * 100, 1)
                : 0,
        ];

        $unmatchedGPUs = HardwareDetection::getUnmatchedGPUs()->take(50);
        $unmatchedCPUs = HardwareDetection::getUnmatchedCPUs()->take(50);

        $recentDetections = HardwareDetection::latest()
            ->take(20)
            ->get();

        return view('admin.hardware-detections', compact('stats', 'unmatchedGPUs', 'unmatchedCPUs', 'recentDetections'));
    }
}
