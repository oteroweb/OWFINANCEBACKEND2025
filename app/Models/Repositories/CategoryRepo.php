<?php

namespace App\Models\Repositories;

use App\Models\Entities\Category;

class CategoryRepo
{
    /**
     * OWF-370: $businessId acota al contexto de contabilidad. Con empresa: categorías de esa
     * empresa + las globales (user_id null); sin empresa: solo personales/globales, nunca de empresa.
     */
    private function scopeContext($query, $userId, $businessId): void
    {
        if ($businessId) {
            $query->where(function ($q) use ($businessId) {
                $q->where('business_id', (int) $businessId)
                  ->orWhere(function ($g) { $g->whereNull('user_id')->whereNull('business_id'); });
            });
            return;
        }
        $query->whereNull('business_id');
        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            });
        }
    }

    public function all($userId = null, $businessId = null)
    {
        $query = Category::query()->with('jars');
        $this->scopeContext($query, $userId, $businessId);
        return $query->get();
    }

    public function allActive($userId = null, $businessId = null)
    {
        $query = Category::where('active', 1)->with('jars');
        $this->scopeContext($query, $userId, $businessId);
        return $query->get();
    }

    public function find($id, $userId = null)
    {
        $query = Category::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }
        return $query->find($id);
    }

    public function store(array $data)
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data)
    {
        $category->update($data);
        return $category;
    }

    public function delete(Category $category)
    {
        return $category->delete();
    }

    public function withTrashed()
    {
        return Category::withTrashed()->get();
    }
}
