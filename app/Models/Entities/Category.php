<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use App\Models\Entities\Jar;

class Category extends Model
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $fillable = [
        'name',
        'active',
        'date',
        'parent_id',
    'user_id',
    'icon',
    'transaction_type_id',
    'include_in_balance',
    'type',
    'sort_order',
    ];

    protected $appends = ['jar_slug'];

    protected $casts = [
        'date'       => 'datetime:Y-m-d',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
    'include_in_balance' => 'boolean',
    ];

    private static array $JAR_SLUG_MAP = [
        'Vivienda'        => 'necesidades',
        'Supermercado'    => 'necesidades',
        'Servicios'       => 'necesidades',
        'Transporte'      => 'necesidades',
        'Salud'           => 'necesidades',
        'Restaurantes'    => 'diversion',
        'Entretenimiento' => 'diversion',
        'Ropa'            => 'diversion',
        'Suscripciones'   => 'diversion',
        'Educación'       => 'educacion',
        'Inversión'       => 'ahorro',
        'Otros'           => 'reservas',
    ];

    // Reverse of the frontend's JAR_SLUG_NAMES (src/utils/txCatalog.ts) — jar name → slug.
    private static array $JAR_NAME_TO_SLUG = [
        'Necesidades básicas' => 'necesidades',
        'Diversión'           => 'diversion',
        'Ahorro'              => 'ahorro',
        'Educación'           => 'educacion',
        'Reservas'            => 'reservas',
    ];

    public function getJarSlugAttribute(): ?string
    {
        // Real assignment via jar_category pivot takes precedence over the legacy name map,
        // so custom/user-created categories (e.g. "Familia") resolve correctly.
        $assignedJar = $this->relationLoaded('jars') ? $this->jars->first() : $this->jars()->first();
        if ($assignedJar && isset(self::$JAR_NAME_TO_SLUG[$assignedJar->name])) {
            return self::$JAR_NAME_TO_SLUG[$assignedJar->name];
        }

        return self::$JAR_SLUG_MAP[$this->name] ?? null;
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class, 'transaction_type_id');
    }

    public function jars()
    {
        return $this->belongsToMany(Jar::class, 'jar_category');
    }

    protected static function newFactory()
    {
        return \Database\Factories\CategoryFactory::new();
    }
}
