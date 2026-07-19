<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Archive;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $archives = Archive::query()->visibleTo($request->user());
        $unitId = $request->user()->unit_id;
        $restrict = ! in_array($request->user()->role, ['super_admin', 'auditor'], true);

        return response()->json([
            'success' => true,
            'data' => [
                'total_archives' => (clone $archives)->count(),
                'incoming' => (clone $archives)->where('type', 'INCOMING')->count(),
                'outgoing' => (clone $archives)->where('type', 'OUTGOING')->count(),
                'general' => (clone $archives)->where('type', 'GENERAL')->count(),
                'confidential' => (clone $archives)->whereIn('security_level', ['CONFIDENTIAL', 'STRICTLY_CONFIDENTIAL'])->count(),
                'pending_verification' => (clone $archives)->where('status', 'PENDING')->count(),
                'active_loans' => DB::table('archive_loans')->when($restrict, fn ($q) => $q->where('unit_id', $unitId))->where('status', 'BORROWED')->count(),
                'overdue_loans' => DB::table('archive_loans')->when($restrict, fn ($q) => $q->where('unit_id', $unitId))->where('status', 'BORROWED')->whereDate('due_at', '<', now())->count(),
                'pending_dispositions' => DB::table('dispositions')->where('assigned_to', $request->user()->id)->whereIn('status', ['UNREAD', 'READ', 'IN_PROGRESS'])->count(),
            ],
        ]);
    }
}
