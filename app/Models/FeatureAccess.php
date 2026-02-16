<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeatureAccess extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'route',
        'icon',
        'module',
        'roles',
        'parent_id',
    ];
    
    public function parent()
    {
        return $this->belongsTo(FeatureAccess::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(FeatureAccess::class, 'parent_id')->orderBy('id');
    }
}
