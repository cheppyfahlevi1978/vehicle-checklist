<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CourierOrderController extends Controller
{
    private function authorizeRole(Request $request): void { abort_unless(in_array($request->user()->role,['courier','admin'],true),403); }

    public function available(Request $request): JsonResponse
    {
        $this->authorizeRole($request);
        $rows=Order::where('status','READY_FOR_PICKUP')->whereNull('courier_id')->with(['store:id,name,address,latitude,longitude','delivery'])->latest()->paginate(30);
        return response()->json(['success'=>true,'data'=>$rows]);
    }

    public function mine(Request $request): JsonResponse
    {
        $this->authorizeRole($request);
        $rows=Order::where('courier_id',$request->user()->id)->with(['store:id,name,address','buyer:id,name,phone','delivery'])->latest()->paginate(30);
        return response()->json(['success'=>true,'data'=>$rows]);
    }

    public function claim(Request $request, Order $order): JsonResponse
    {
        $this->authorizeRole($request);
        DB::transaction(function () use ($request,$order) {
            $locked=Order::lockForUpdate()->findOrFail($order->id);
            abort_unless($locked->status==='READY_FOR_PICKUP' && $locked->courier_id===null,409,'Order sudah diambil kurir lain.');
            $locked->update(['courier_id'=>$request->user()->id]);
            $locked->delivery()->update(['courier_id'=>$request->user()->id,'status'=>'COURIER_ASSIGNED','accepted_at'=>now()]);
        });
        return response()->json(['success'=>true,'message'=>'Tugas pengantaran berhasil diambil.']);
    }

    public function updateStatus(Request $request, Order $order, OrderWorkflow $workflow): JsonResponse
    {
        $this->authorizeRole($request);
        abort_unless($request->user()->role==='admin' || $order->courier_id===$request->user()->id,403);
        $data=$request->validate(['status'=>['required',Rule::in(['PICKED_UP','DELIVERED'])],'note'=>['nullable','string','max:500']]);
        $updated=$workflow->transition($order,$data['status']);
        if ($data['status']==='PICKED_UP') $updated->delivery()->update(['status'=>'PICKED_UP','picked_up_at'=>now(),'courier_note'=>$data['note']??null]);
        if ($data['status']==='DELIVERED') {
            $updated->delivery()->update(['status'=>'DELIVERED','delivered_at'=>now(),'courier_note'=>$data['note']??null]);
            if ($updated->payment_method==='COD') $updated->update(['payment_status'=>'PAID']);
        }
        return response()->json(['success'=>true,'message'=>'Status pengantaran diperbarui.','data'=>$updated->fresh(['delivery'])]);
    }
}
