<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MasterDataController extends Controller
{
    public function units(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => DB::table('units')->where('is_active', true)->orderBy('name')->get()]);
    }

    public function classifications(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => DB::table('archive_classifications')->where('is_active', true)->orderBy('code')->get()]);
    }

    public function locations(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => DB::table('archive_locations')->orderBy('code')->get()]);
    }
}
