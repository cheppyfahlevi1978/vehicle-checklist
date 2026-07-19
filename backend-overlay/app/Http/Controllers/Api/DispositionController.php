<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Archive;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DispositionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rows = DB::table('dispositions')
            ->join('archives', 'archives.id', '=', 'dispositions.archive_id')
            ->where(fn ($q) => $q->where('dispositions.assigned_to', $request->user()->id)->orWhere('dispositions.created_by', $request->user()->id))
            ->select('dispositions.*', 'archives.archive_number', 'archives.title as archive_title')
            ->latest('dispositions.created_at')
            ->paginate(30);
        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'archive_id' => ['required', 'integer', 'exists:archives,id'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'instruction' => ['required', 'string', 'max:2000'],
            'priority' => ['nullable', Rule::in(['NORMAL', 'HIGH', 'URGENT'])],
            'due_at' => ['nullable', 'date'],
        ]);
        $archive = Archive::findOrFail($data['archive_id']);
        abort_unless(in_array($request->user()->role, ['super_admin', 'leader', 'archive_admin'], true) || $archive->unit_id === $request->user()->unit_id, 403);
        $id = DB::table('dispositions')->insertGetId([
            ...$data,
            'created_by' => $request->user()->id,
            'status' => 'UNREAD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return response()->json(['success' => true, 'message' => 'Disposisi dibuat.', 'data' => DB::table('dispositions')->find($id)], 201);
    }

    public function updateStatus(Request $request, int $disposition): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['READ', 'IN_PROGRESS', 'DONE'])],
            'follow_up_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $row = DB::table('dispositions')->where('id', $disposition)->where('assigned_to', $request->user()->id)->first();
        abort_unless($row, 404);
        DB::table('dispositions')->where('id', $disposition)->update([...$data, 'read_at' => $data['status'] === 'READ' ? now() : $row->read_at, 'completed_at' => $data['status'] === 'DONE' ? now() : null, 'updated_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Status disposisi diperbarui.']);
    }
}
