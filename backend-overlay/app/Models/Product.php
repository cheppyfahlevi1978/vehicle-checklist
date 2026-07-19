<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['store_id', 'name', 'sku', 'description', 'category', 'price', 'stock', 'unit', 'image_path', 'is_active'];
    protected function casts(): array { return ['price' => 'decimal:2', 'stock' => 'integer', 'is_active' => 'boolean']; }
    public function store() { return $this->belongsTo(Store::class); }
}
