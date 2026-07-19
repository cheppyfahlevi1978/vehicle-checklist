<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Archive;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('archive_loans')
            ->join('archives', 'archives.id', '=', 'archive_loans.archive_id')
            ->select('archive_loans.*', 'archives.archive_number', 'archives.title as archive_title')
            ->when(! in_array($request->user()->role, ['super_admin', 'auditor', 'archive_admin'], true), fn ($q) => $q->where('archive_loans.requested_by', $request->user()->id))
            ->latest('archive_loans.created_at');
        return response()->json(['success' => true, 'data' => $query->paginate(30)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'archive_id' => ['required', 'integer', 'exists:archives,id'],
            'reason' => ['required', 'string', 'max:1000'],
            'due_at' => ['required', 'date', 'after:today'],
        ]);
        $archive = Archive::findOrFail($data['archive_id']);
        abort_unless(in_array($request->user()->role, ['super_admin', 'auditor'], true) || $archive->unit_id === $request->user()->unit_id, 403);
        $id = DB::table('archive_loans')->insertGetId([
            ...$data,
            'unit_id' => $request->user()->unit_id,
            'requested_by' => $request->user()->id,
            'status' => 'REQUESTED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true, 'message' => 'Permohonan peminjaman dibuat.', 'data' => DB::table('archive_loans')->find($id)], 201);
    }

    public function approve(Request $request, int $loan): JsonResponse
    {
        abort_unless(in_array($request->user()->role, ['super_admin', 'archive_admin'], true), 403);
        $affected = DB::table('archive_loans')->where('id', $loan)->where('status', 'REQUESTED')->update([
            'status' => 'BORROWED',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'borrowed_at' => now(),
            'updated_at' => now(),
        ]);
        abort_unless($affected, 409, 'Peminjaman tidak dapat disetujui.');
        return response()->json(['success' => true, 'message' => 'Peminjaman disetujui.']);
    }

    public function markReturned(Request $request, int $loan): JsonResponse
    {
        $data = $request->validate(['return_condition' => ['nullable', 'string', 'max:1000']]);
        abort_unless(in_array($request->user()->role, ['super_admin', 'archive_admin'], true), 403);
        $affected = DB::table('archive_loans')->where('id', $loan)->where('status', 'BORROWED')->update([
            'status' => 'RETURNED',
            'return_condition' => $data['return_condition'] ?? null,
            'returned_at' => now(),
            'received_by' => $request->user()->id,
            'updated_at' => now(),
        ]);
        abort_unless($affected, 409, 'Peminjaman tidak dapat dikembalikan.');
        return response()->json(['success' => true, 'message' => 'Arsip telah dikembalikan.']);
    }
}
