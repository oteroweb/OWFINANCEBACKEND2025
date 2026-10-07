<?php

namespace App\Models\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use SoftDeletes;

    public const ROLES = ['owner', 'accountant', 'viewer'];

    /** D-009: paleta cerrada — ningún color cae cerca del verde de ingreso/rojo de gasto/ámbar de alerta. */
    public const PALETTE = ['#3B5BDB', '#0EA5E9', '#4338CA', '#8B5CF6', '#7E22CE', '#A21CAF', '#475569', '#78716C'];

    protected $fillable = [
        'owner_user_id', 'name', 'tax_id', 'currency_id', 'mode', 'color',
        'profile', 'onboarded_at', 'active',
    ];

    protected $casts = [
        'profile'      => 'array',
        'onboarded_at' => 'datetime',
        'active'       => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members()
    {
        return $this->hasMany(BusinessUser::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    /** Rol ACTIVO del usuario en esta empresa (null si no es miembro activo). */
    public function roleFor(?int $userId): ?string
    {
        if (!$userId) return null;
        return $this->members()->where('user_id', $userId)->where('status', 'active')->value('role');
    }

    /** Rol activo de un usuario en una empresa dada por id, sin cargar el modelo. */
    public static function roleOf(?int $businessId, ?int $userId): ?string
    {
        if (!$businessId || !$userId) return null;
        return BusinessUser::where('business_id', $businessId)
            ->where('user_id', $userId)->where('status', 'active')->value('role');
    }

    /** Siguiente color libre de la paleta para un usuario (vuelve al inicio si se agotan). */
    public static function nextColorFor(int $userId): string
    {
        $used = static::where('owner_user_id', $userId)->pluck('color')->all();
        foreach (self::PALETTE as $c) {
            if (!in_array($c, $used, true)) return $c;
        }
        return self::PALETTE[count($used) % count(self::PALETTE)];
    }
}
