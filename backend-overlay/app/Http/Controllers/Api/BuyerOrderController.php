<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuyerOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(in_array($request->user()->role,['buyer','admin'],true),403);
        $rows = Order::where('buyer_id',$request->user()->id)->with(['store:id,name','items','delivery'])->latest()->paginate(20);
        return response()->json(['success'=>true,'data'=>$rows]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(in_array($request->user()->role,['buyer','admin'],true),403);
        $data = $request->validate([
            'store_id'=>['required','integer','exists:stores,id'],
            'items'=>['required','array','min:1'],
            'items.*.product_id'=>['required','integer','exists:products,id'],
            'items.*.quantity'=>['required','integer','min:1','max:100'],
            'recipient_name'=>['required','string','max:120'],
            'recipient_phone'=>['required','string','max:30'],
            'delivery_address'=>['required','string','max:1000'],
            'latitude'=>['nullable','numeric','between:-90,90'],
            'longitude'=>['nullable','numeric','between:-180,180'],
            'buyer_note'=>['nullable','string','max:1000'],
            'payment_method'=>['required','in:COD,BANK_TRANSFER,EWALLET'],
        ]);

        $order = DB::transaction(function () use ($data,$request) {
            $products = Product::whereIn('id',collect($data['items'])->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);
                if (! $product || $product->store_id != $data['store_id'] || ! $product->is_active || $product->stock < $item['quantity']) {
                    throw ValidationException::withMessages(['items'=>['Produk tidak tersedia atau stok tidak cukup.']]);
                }
                $subtotal += (float) $product->price * $item['quantity'];
            }
            $deliveryFee = (float) env('PILOT_DELIVERY_FEE',10000);
            $serviceFee = (float) env('PILOT_SERVICE_FEE',2000);
            $order = Order::create([
                'order_number'=>'ORD-'.now()->format('YmdHis').'-'.random_int(100,999),
                'buyer_id'=>$request->user()->id,'store_id'=>$data['store_id'],'status'=>'PLACED',
                'payment_method'=>$data['payment_method'],'payment_status'=>$data['payment_method']==='COD'?'UNPAID':'PENDING',
                'subtotal'=>$subtotal,'delivery_fee'=>$deliveryFee,'service_fee'=>$serviceFee,'total'=>$subtotal+$deliveryFee+$serviceFee,
                'recipient_name'=>$data['recipient_name'],'recipient_phone'=>$data['recipient_phone'],'delivery_address'=>$data['delivery_address'],
                'latitude'=>$data['latitude']??null,'longitude'=>$data['longitude']??null,'buyer_note'=>$data['buyer_note']??null,
            ]);
            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);
                $order->items()->create(['product_id'=>$product->id,'product_name'=>$product->name,'price'=>$product->price,'quantity'=>$item['quantity'],'subtotal'=>(float)$product->price*$item['quantity']]);
                $product->decrement('stock',$item['quantity']);
            }
            Delivery::create(['order_id'=>$order->id,'status'=>'WAITING_MERCHANT']);
            return $order;
        });

        return response()->json(['success'=>true,'message'=>'Pesanan dibuat.','data'=>$order->load(['items','store','delivery'])],201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->buyer_id===$request->user()->id || $request->user()->role==='admin',403);
        return response()->json(['success'=>true,'data'=>$order->load(['items','store','courier','delivery'])]);
    }

    public function cancel(Request $request, Order $order, OrderWorkflow $workflow): JsonResponse
    {
        abort_unless($order->buyer_id===$request->user()->id || $request->user()->role==='admin',403);
        $data=$request->validate(['reason'=>['required','string','max:500']]);
        $workflow->transition($order,'CANCELLED');
        $order->update(['cancel_reason'=>$data['reason']]);
        return response()->json(['success'=>true,'message'=>'Pesanan dibatalkan.']);
    }
}
