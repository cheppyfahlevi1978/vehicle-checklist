<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['order_number','buyer_id','store_id','courier_id','status','payment_method','payment_status','subtotal','delivery_fee','service_fee','total','recipient_name','recipient_phone','delivery_address','latitude','longitude','buyer_note','merchant_note','cancel_reason'];
    protected function casts(): array { return ['subtotal'=>'decimal:2','delivery_fee'=>'decimal:2','service_fee'=>'decimal:2','total'=>'decimal:2','latitude'=>'decimal:7','longitude'=>'decimal:7']; }
    public function buyer() { return $this->belongsTo(User::class, 'buyer_id'); }
    public function store() { return $this->belongsTo(Store::class); }
    public function courier() { return $this->belongsTo(User::class, 'courier_id'); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function delivery() { return $this->hasOne(Delivery::class); }
}
