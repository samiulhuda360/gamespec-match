<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentRating extends Model
{
    protected $table = 'content_ratings';
    public $timestamps = false;
    const UPDATED_AT = null;

    protected $fillable = [
        'app_id',
        'rating_system',
        'rating',
        'descriptors'
    ];

    /**
     * Get the app that owns the content rating
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'app_id');
    }

    /**
     * Get formatted rating system name
     */
    public function getRatingSystemNameAttribute()
    {
        return strtoupper($this->rating_system);
    }

    /**
     * Get rating color based on system and rating
     */
    public function getRatingColorAttribute()
    {
        switch ($this->rating_system) {
            case 'esrb':
                return $this->getEsrbColor();
            case 'pegi':
                return $this->getPegiColor();
            default:
                return 'gray';
        }
    }

    private function getEsrbColor()
    {
        switch ($this->rating) {
            case 'E':
            case 'E10+':
                return 'green';
            case 'T':
                return 'blue';
            case 'M':
                return 'yellow';
            case 'AO':
                return 'red';
            default:
                return 'gray';
        }
    }

    private function getPegiColor()
    {
        $age = intval($this->rating);
        if ($age <= 7) return 'green';
        if ($age <= 12) return 'blue';
        if ($age <= 16) return 'yellow';
        return 'red';
    }
}
