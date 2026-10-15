<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'reviews';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    protected $fillable = [
        'app_id',
        'total_reviews',
        'total_positive',
        'total_negative',
        'review_score_desc',
    ];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'app_id');
    }
}