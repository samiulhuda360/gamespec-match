<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $table = 'movies';
    public $timestamps = false;

    protected $fillable = ['app_id','steam_movie_id','name','thumbnail','webm_480','mp4_480','highlight'];
}
