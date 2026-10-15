namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompatibilityCache extends Model
{
    protected $table = 'compatibility_cache';

    protected $fillable = [
        'hardware_id',
        'app_id',
        'can_run_min',
        'can_run_rec',
        'bottlenecks',
    ];

    protected $casts = [
        'bottlenecks' => 'array',
    ];

    public function hardware()
    {
        return $this->belongsTo(UserHardware::class, 'hardware_id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'app_id');
    }
}