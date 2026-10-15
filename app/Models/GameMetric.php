<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMetric extends Model
{
    protected $table = 'game_metrics';
    protected $primaryKey = 'id';

    public $timestamps = true;
    const UPDATED_AT = 'updated_at';
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'app_id',
        'min_requirement_score',
        'rec_requirement_score',
        'intensity_tier',
        'has_pc_requirements',
        'has_mac_requirements',
        'has_linux_requirements',
        'popularity_score',
    ];

    protected $casts = [
        'min_requirement_score' => 'decimal:2',
        'rec_requirement_score' => 'decimal:2',
        'has_pc_requirements' => 'boolean',
        'has_mac_requirements' => 'boolean',
        'has_linux_requirements' => 'boolean',
        'popularity_score' => 'integer',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * The app this metric belongs to
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'app_id');
    }

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Check if game is low-end friendly
     */
    public function isLowEndFriendly(): bool
    {
        return $this->intensity_tier === 'light';
    }

    /**
     * Check if game is high-end/demanding
     */
    public function isHighEnd(): bool
    {
        return in_array($this->intensity_tier, ['high-end', 'ultra']);
    }

    /**
     * Get intensity tier display name
     */
    public function getIntensityDisplayAttribute(): string
    {
        return match($this->intensity_tier) {
            'light' => '💻 Light',
            'medium' => '⚙️ Medium',
            'high-end' => '🔥 High-End',
            'ultra' => '💎 Ultra',
            default => 'Unknown',
        };
    }

    /**
     * Get average requirement score
     */
    public function getAverageRequirementAttribute(): ?float
    {
        if (!$this->min_requirement_score || !$this->rec_requirement_score) {
            return null;
        }

        return ($this->min_requirement_score + $this->rec_requirement_score) / 2;
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope for low-end friendly games
     */
    public function scopeLowEnd($query)
    {
        return $query->where('intensity_tier', 'light');
    }

    /**
     * Scope for high-end games
     */
    public function scopeHighEnd($query)
    {
        return $query->whereIn('intensity_tier', ['high-end', 'ultra']);
    }

    /**
     * Scope for popular games
     */
    public function scopePopular($query, $minScore = 1000)
    {
        return $query->where('popularity_score', '>=', $minScore);
    }

    /**
     * Scope for games with specific intensity
     */
    public function scopeIntensity($query, $tier)
    {
        return $query->where('intensity_tier', $tier);
    }
}
