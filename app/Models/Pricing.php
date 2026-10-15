<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    protected $table = 'pricing';
    
    public $timestamps = false;

    protected $fillable = [
        'app_id',
        'currency',
        'initial_price',
        'final_price',
        'discount_percent',
    ];

    protected $casts = [
        'initial_price' => 'integer',
        'final_price' => 'integer',
        'discount_percent' => 'integer',
    ];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'app_id');
    }

    // Helper methods
    public function getInitialPriceInDollarsAttribute()
    {
        return $this->initial_price ? $this->initial_price / 100 : 0;
    }

    public function getFinalPriceInDollarsAttribute()
    {
        return $this->final_price ? $this->final_price / 100 : 0;
    }

    public function hasDiscount()
    {
        return $this->discount_percent > 0;
    }
}