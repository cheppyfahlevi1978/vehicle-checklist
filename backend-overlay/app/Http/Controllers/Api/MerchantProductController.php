<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MerchantProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(in_array($request->user()->role,['merchant','admin'],true),403);
        return response()->json(['success'=>true,'data'=>Product::whereIn('store_id',$request->user()->stores()->pluck('id'))->latest()->paginate(30)]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(in_array($request->user()->role,['merchant','admin'],true),403);
        $data=$this->validated($request);
        abort_unless($request->user()->role==='admin' || $request->user()->stores()->whereKey($data['store_id'])->exists(),403);
        $data['sku']=$data['sku']??strtoupper(Str::random(10));
        return response()->json(['success'=>true,'message'=>'Produk dibuat.','data'=>Product::create($data)],201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        abort_unless($request->user()->role==='admin' || $request->user()->stores()->whereKey($product->store_id)->exists(),403);
        $product->update($this->validated($request,true));
        return response()->json(['success'=>true,'message'=>'Produk diperbarui.','data'=>$product->fresh()]);
    }

    private function validated(Request $request,bool $partial=false): array
    {
        $required=$partial?'sometimes':'required';
        return $request->validate([
            'store_id'=>[$required,'integer','exists:stores,id'],'name'=>[$required,'string','max:255'],'sku'=>['nullable','string','max:100'],
            'description'=>['nullable','string','max:3000'],'category'=>['nullable','string','max:120'],'price'=>[$required,'numeric','min:0'],
            'stock'=>[$required,'integer','min:0'],'unit'=>['nullable','string','max:30'],'is_active'=>['nullable','boolean'],
        ]);
    }
}
