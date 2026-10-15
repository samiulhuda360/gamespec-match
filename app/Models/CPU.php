<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CPU extends Model
{
    protected $table = 'cpus';

    protected $fillable = [
        'manufacturer',
        'name',
        'generation',
        'benchmark_score',
        'cores',
        'threads',
        'base_clock',
        'release_year',
        'is_popular',
    ];

    protected $casts = [
        'benchmark_score' => 'integer',
        'cores' => 'integer',
        'threads' => 'integer',
        'base_clock' => 'decimal:2',
        'release_year' => 'integer',
        'is_popular' => 'boolean',
    ];

    /**
     * Get popular CPUs for dropdown
     */
    public static function getPopular()
    {
        return static::where('is_popular', true)
            ->orderBy('manufacturer')
            ->orderByDesc('benchmark_score')
            ->get();
    }

    /**
     * Get all CPUs grouped by manufacturer
     * Optimized to only select necessary fields
     */
    public static function getAllGrouped()
    {
        return static::select(['id', 'manufacturer', 'name', 'benchmark_score', 'cores', 'threads'])
            ->orderBy('manufacturer')
            ->orderByDesc('benchmark_score')
            ->get()
            ->groupBy('manufacturer');
    }

    /**
     * Search CPUs by name
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
