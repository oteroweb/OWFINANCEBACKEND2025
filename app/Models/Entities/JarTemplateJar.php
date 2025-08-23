<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JarTemplateJar extends Model
{
    use HasFactory;

    protected $fillable = [
        'jar_template_id',
        'name',
        'type',
        'percent',
        'fixed_amount',
        'base_scope',
        'color',
        'sort_order',
    ];

    public function template()
    {
        return $this->belongsTo(JarTemplate::class, 'jar_template_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'jar_template_jar_categories');
    }

    public function baseCategories()
    {
        return $this->belongsToMany(Category::class, 'jar_template_jar_base_categories');
    }

    public function categoryTemplates()
    {
        return $this->belongsToMany(CategoryTemplate::class, 'jar_tpljar_cat_tpl', 'jar_template_jar_id', 'category_template_id');
    }

    public function baseCategoryTemplates()
    {
        return $this->belongsToMany(CategoryTemplate::class, 'jar_tpljar_base_cat_tpl', 'jar_template_jar_id', 'category_template_id');
    }
}
