<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Screenshot extends Model
{
    protected $table = 'screenshots';
    public $timestamps = false;

    protected $fillable = ['app_id','steam_screenshot_id','path_thumbnail','path_full'];
}
