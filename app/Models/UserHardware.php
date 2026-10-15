<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserHardware extends Model
{
    protected $table = 'user_hardware';

    protected $fillable = [
        'user_id',
        'session_id',
        'profile_name',
        'cpu_id',
        'cpu_model',
        'cpu',
        'cpu_score',
        'gpu_id',
        'gpu_model',
        'ram',
        'ram_gb',
        'ram_score',
        'gpu',
        'gpu_score',
        'storage',
        'storage_score',
        'overall_score',
        'platform',
        'is_active',
    ];

    protected $casts = [
        'cpu_score' => 'float',
        'ram_score' => 'float',
        'gpu_score' => 'float',
        'storage_score' => 'float',
        'overall_score' => 'float',
        'is_active' => 'boolean',
        'ram_gb' => 'integer',
    ];

    /**
     * Get the CPU that belongs to this hardware profile
     */
    public function cpu()
    {
        return $this->belongsTo(CPU::class);
    }

    /**
     * Get the GPU that belongs to this hardware profile
     */
    public function gpu()
    {
        return $this->belongsTo(GPU::class);
    }

    /**
     * A signed-in user's profile belongs to that user; a guest's profile belongs to the
     * session that created it (or the session it was made active in).
     */
    public function isOwnedByVisitor(): bool
    {
        if ($this->user_id !== null) {
            return auth()->check() && (int) $this->user_id === (int) auth()->id();
        }

        return ($this->session_id !== null && hash_equals($this->session_id, session()->getId()))
            || (int) session('active_hardware_id') === (int) $this->id;
    }

    /** The profile the visitor is checking games against, if any. */
    public static function activeForVisitor(): ?self
    {
        $id = session('active_hardware_id');
        $hardware = $id ? static::find($id) : null;
        if ($hardware && $hardware->isOwnedByVisitor()) {
            return $hardware;
        }

        // A signed-in user starting a new session gets their most recent profile back.
        $latest = auth()->check()
            ? static::where('user_id', auth()->id())->latest('updated_at')->first()
            : null;
        session()->put('active_hardware_id', $latest?->id);

        return $latest;
    }

    /** After sign-in, profiles made as a guest in this browser move to the account. */
    public static function claimGuestProfiles(string $guestSessionId, int $userId): void
    {
        $active = session('active_hardware_id');
        static::whereNull('user_id')
            ->where(fn ($q) => $q->where('session_id', $guestSessionId)->when($active, fn ($q) => $q->orWhere('id', $active)))
            ->update(['user_id' => $userId]);
    }
}
