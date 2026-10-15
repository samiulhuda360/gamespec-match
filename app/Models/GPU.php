<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GPU extends Model
{
    protected $table = 'gpus';

    protected $fillable = [
        'manufacturer',
        'name',
        'series',
        'vram_gb',
        'benchmark_score',
        'release_year',
        'is_popular',
    ];

    protected $casts = [
        'vram_gb' => 'integer',
        'benchmark_score' => 'integer',
        'release_year' => 'integer',
        'is_popular' => 'boolean',
    ];

    /**
     * Get popular GPUs for dropdown
     */
    public static function getPopular()
    {
        return static::where('is_popular', true)
            ->orderBy('manufacturer')
            ->orderByDesc('benchmark_score')
            ->get();
    }

    /**
     * Get all GPUs grouped by manufacturer
     * Optimized to only select necessary fields
     */
    public static function getAllGrouped()
    {
        return static::select(['id', 'manufacturer', 'name', 'vram_gb', 'benchmark_score'])
            ->orderBy('manufacturer')
            ->orderByDesc('benchmark_score')
            ->get()
            ->groupBy('manufacturer');
    }

    /**
     * Search GPUs by name
     */
    public static function search($query)
    {
        return static::where('name', 'LIKE', "%{$query}%")
            ->orderByDesc('is_popular')
            ->orderByDesc('benchmark_score')
            ->limit(20)
            ->get();
    }
}
