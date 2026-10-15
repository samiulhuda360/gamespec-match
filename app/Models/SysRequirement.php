<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysRequirement extends Model
{
    protected $table = 'sys_requirements';
    public $timestamps = false;

    protected $fillable = [
        'app_id','platform','min_html','rec_html',
        'min_cpu','min_ram','min_gpu','min_storage','min_directx',
        'rec_cpu','rec_ram','rec_gpu','rec_storage','rec_directx'
    ];
}
