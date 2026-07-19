<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MerchantOrderController extends Controller
{
    private function storeIds(Request $request) { return $request->user()->stores()->pluck('id'); }

    public function index(Request $request): JsonResponse
    {
        abort_unless(in_array($request->user()->role,['merchant','admin'],true),403);
        $rows=Order::whereIn('store_id',$this->storeIds($request))->with(['buyer:id,name,phone','items','delivery'])->latest()->paginate(30);
        return response()->json(['success'=>true,'data'=>$rows]);
    }

    public function updateStatus(Request $request, Order $order, OrderWorkflow $workflow): JsonResponse
    {
        abort_unless($request->user()->role==='admin' || $request->user()->stores()->whereKey($order->store_id)->exists(),403);
        $data=$request->validate(['status'=>['required',Rule::in(['ACCEPTED','PREPARING','READY_FOR_PICKUP','CANCELLED'])],'note'=>['nullable','string','max:500']]);
        $updated=$workflow->transition($order,$data['status']);
        $updated->update(['merchant_note'=>$data['note']??$updated->merchant_note]);
        if ($data['status']==='READY_FOR_PICKUP') $updated->delivery()->update(['status'=>'SEARCHING_COURIER']);
        return response()->json(['success'=>true,'message'=>'Status diperbarui.','data'=>$updated->fresh(['delivery'])]);
    }
}
