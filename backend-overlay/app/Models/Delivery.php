<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = ['order_id','courier_id','status','accepted_at','picked_up_at','delivered_at','proof_path','courier_note'];
    protected function casts(): array { return ['accepted_at'=>'datetime','picked_up_at'=>'datetime','delivered_at'=>'datetime']; }
    public function order() { return $this->belongsTo(Order::class); }
}
