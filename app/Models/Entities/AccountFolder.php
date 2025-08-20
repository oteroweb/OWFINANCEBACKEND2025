<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'parent_id',
    ];

    /**
     * The owner user of the folder.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Parent folder relation.
     */
    public function parent()
    {
        return $this->belongsTo(AccountFolder::class, 'parent_id');
    }

    /**
     * Children folders.
     */
    public function children()
    {
        return $this->hasMany(AccountFolder::class, 'parent_id');
    }

    /**
     * Accounts in this folder via pivot.
     */
    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'account_user', 'folder_id', 'account_id')
            ->withTimestamps()
            ->withPivot('is_owner', 'sort_order');
    }
}
