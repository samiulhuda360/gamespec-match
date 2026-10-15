<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HardwareDetection extends Model
{
    protected $fillable = [
        'gpu_raw',
        'cpu_raw',
        'cpu_cores',
        'cpu_threads',
        'ram_gb',
        'platform',
        'user_agent',
        'matched_gpu_id',
        'matched_cpu_id',
        'gpu_matched',
        'cpu_matched',
        'detection_method',
        'ip_address',
        'detection_count',
    ];

    protected $casts = [
        'gpu_matched' => 'boolean',
        'cpu_matched' => 'boolean',
        'cpu_cores' => 'integer',
        'cpu_threads' => 'integer',
        'ram_gb' => 'integer',
        'detection_count' => 'integer',
    ];

    /**
     * Get unmatched GPU detections for review
     */
    public static function getUnmatchedGPUs()
    {
        return self::where('gpu_matched', false)
            ->whereNotNull('gpu_raw')
            ->select('gpu_raw', \DB::raw('COUNT(*) as count'))
            ->groupBy('gpu_raw')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Get unmatched CPU detections for review
     */
    public static function getUnmatchedCPUs()
    {
        return self::where('cpu_matched', false)
            ->whereNotNull('cpu_raw')
            ->select('cpu_raw', 'cpu_cores', 'cpu_threads', \DB::raw('COUNT(*) as count'))
            ->groupBy('cpu_raw', 'cpu_cores', 'cpu_threads')
            ->orderBy('count', 'desc')
            ->get();
    }
}
