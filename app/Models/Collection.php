<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $table = 'collections';

    protected $fillable = [
        'slug','title','subtitle','type',
        'year','week_year','week_no',
        'intro','seo_title','seo_description','seo_keywords','hero_image',
        'is_published','published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];
}
