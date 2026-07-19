<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function stores(Request $request): JsonResponse
    {
        $rows = Store::where('is_active', true)
            ->when($request->filled('search'), fn ($q) => $q->where('name','like','%'.$request->string('search').'%'))
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->paginate(20);
        return response()->json(['success'=>true,'data'=>$rows]);
    }

    public function products(Request $request): JsonResponse
    {
        $rows = Product::with('store:id,name,slug')
            ->where('is_active', true)->where('stock','>',0)
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id',$request->integer('store_id')))
            ->when($request->filled('category'), fn ($q) => $q->where('category',$request->string('category')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($x) => $x->where('name','like','%'.$request->string('search').'%')->orWhere('description','like','%'.$request->string('search').'%')))
            ->latest()->paginate(30);
        return response()->json(['success'=>true,'data'=>$rows]);
    }
}
