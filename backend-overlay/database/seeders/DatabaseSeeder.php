<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password=Hash::make(env('ADMIN_PASSWORD','GantiPasswordKuat123!'));
        User::updateOrCreate(['email'=>env('ADMIN_EMAIL','admin@ias4u.my.id')],['name'=>env('ADMIN_NAME','Administrator'),'username'=>'admin','password'=>$password,'role'=>'admin','is_active'=>true]);
        $buyer=User::updateOrCreate(['email'=>'buyer@ias4u.my.id'],['name'=>'Demo Buyer','username'=>'buyer','phone'=>'628111111111','password'=>Hash::make('Buyer123!'),'role'=>'buyer','is_active'=>true]);
        $merchant=User::updateOrCreate(['email'=>'merchant@ias4u.my.id'],['name'=>'Demo Merchant','username'=>'merchant','phone'=>'628122222222','password'=>Hash::make('Merchant123!'),'role'=>'merchant','is_active'=>true]);
        User::updateOrCreate(['email'=>'courier@ias4u.my.id'],['name'=>'Demo Courier','username'=>'courier','phone'=>'628133333333','password'=>Hash::make('Courier123!'),'role'=>'courier','is_active'=>true]);
        $store=Store::updateOrCreate(['slug'=>'toko-demo'],['owner_id'=>$merchant->id,'name'=>'Toko Demo','description'=>'Toko contoh multi-produk','phone'=>'628122222222','address'=>'Surabaya','is_active'=>true]);
        Product::updateOrCreate(['store_id'=>$store->id,'sku'=>'DEMO-001'],['name'=>'Produk Contoh','description'=>'Produk umum untuk pengujian','category'=>'Umum','price'=>25000,'stock'=>100,'unit'=>'pcs','is_active'=>true]);
    }
}
