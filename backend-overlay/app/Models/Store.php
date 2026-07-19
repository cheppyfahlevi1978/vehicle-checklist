<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = ['owner_id', 'name', 'slug', 'description', 'phone', 'address', 'latitude', 'longitude', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7']; }
    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function products() { return $this->hasMany(Product::class); }
}
