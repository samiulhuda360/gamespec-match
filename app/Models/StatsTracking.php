<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatsTracking extends Model
{
    protected $table = 'stats_tracking';
    
    protected $fillable = [
        'stat_type',
        'app_id',
        'hardware_id',
        'session_id',
        'ip_hash',
        'user_agent',
    ];

    const UPDATED_AT = null; // Only has created_at

    /**
     * Quick helper to track events
     */
    public static function track($type, $appId = null, $hardwareId = null)
    {
        return self::create([
            'stat_type' => $type,
            'app_id' => $appId,
            'hardware_id' => $hardwareId,
            'session_id' => session()->getId(),
            'ip_hash' => hash('sha256', request()->ip()),
            'user_agent' => request()->userAgent(),
        ]);
    }
    
    /**
     * Relationships
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id');
    }
    
    public function hardware()
    {
        return $this->belongsTo(UserHardware::class, 'hardware_id');
    }
}