<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Archive;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArchiveController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Archive::query()
            ->visibleTo($request->user())
            ->with(['versions' => fn ($q) => $q->limit(1)])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('classification_id'), fn ($q) => $q->where('classification_id', $request->integer('classification_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($inner) => $inner->where('archive_number', 'like', $term)
                    ->orWhere('document_number', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhere('subject', 'like', $term)
                    ->orWhere('keywords', 'like', $term));
            })
            ->latest();

        return response()->json(['success' => true, 'data' => $query->paginate(30)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateArchive($request);
        $user = $request->user();
        $data['unit_id'] = in_array($user->role, ['super_admin'], true)
            ? ($data['unit_id'] ?? $user->unit_id)
            : $user->unit_id;
        $data['created_by'] = $user->id;
        $data['archive_number'] = $this->nextArchiveNumber($data['type'], (int) $data['classification_id']);

        $archive = DB::transaction(function () use ($data, $request, $user) {
            $archive = Archive::create($data);
            if ($request->hasFile('file')) {
                $this->storeVersion($archive, $request->file('file'), 'Dokumen awal', $user->id, true);
            }
            $this->audit($user->id, 'archive.created', $archive->id, $request);
            return $archive;
        });

        return response()->json(['success' => true, 'message' => 'Arsip berhasil dibuat.', 'data' => $archive->load('versions')], 201);
    }

    public function show(Request $request, Archive $archive): JsonResponse
    {
        $this->authorizeArchive($request, $archive);
        return response()->json(['success' => true, 'data' => $archive->load('versions')]);
    }

    public function update(Request $request, Archive $archive): JsonResponse
    {
        $this->authorizeArchive($request, $archive, true);
        $data = $this->validateArchive($request, true);
        unset($data['unit_id']);
        $archive->update($data);
        $this->audit($request->user()->id, 'archive.updated', $archive->id, $request);
        return response()->json(['success' => true, 'message' => 'Arsip diperbarui.', 'data' => $archive->fresh()]);
    }

    public function uploadVersion(Request $request, Archive $archive): JsonResponse
    {
        $this->authorizeArchive($request, $archive, true);
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:'.env('MAX_ARCHIVE_UPLOAD_KB', 20480)],
            'change_note' => ['nullable', 'string', 'max:500'],
            'is_final' => ['nullable', 'boolean'],
        ]);
        $version = $this->storeVersion($archive, $request->file('file'), $data['change_note'] ?? null, $request->user()->id, (bool) ($data['is_final'] ?? false));
        $this->audit($request->user()->id, 'archive.version_uploaded', $archive->id, $request);
        return response()->json(['success' => true, 'message' => 'Versi dokumen diunggah.', 'data' => $version], 201);
    }

    public function download(Request $request, Archive $archive)
    {
        $this->authorizeArchive($request, $archive);
        $version = $archive->versions()->firstOrFail();
        abort_unless(Storage::disk('local')->exists($version->file_path), 404, 'File tidak ditemukan.');
        $this->audit($request->user()->id, 'archive.downloaded', $archive->id, $request);
        return Storage::disk('local')->download($version->file_path, $version->original_name, ['Cache-Control' => 'private, no-store']);
    }

    public function destroy(Request $request, Archive $archive): JsonResponse
    {
        $this->authorizeArchive($request, $archive, true);
        abort_unless(in_array($request->user()->role, ['super_admin', 'archive_admin'], true), 403);
        $archive->update(['status' => 'INACTIVE']);
        $this->audit($request->user()->id, 'archive.inactivated', $archive->id, $request);
        return response()->json(['success' => true, 'message' => 'Arsip dinonaktifkan, bukan dihapus permanen.']);
    }

    private function validateArchive(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'classification_id' => [Rule::requiredIf(! $updating), 'integer', 'exists:archive_classifications,id'],
            'location_id' => ['nullable', 'integer', 'exists:archive_locations,id'],
            'agenda_number' => ['nullable', 'string', 'max:100'],
            'document_number' => ['nullable', 'string', 'max:150'],
            'type' => [Rule::requiredIf(! $updating), Rule::in(['INCOMING', 'OUTGOING', 'GENERAL'])],
            'title' => [Rule::requiredIf(! $updating), 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:1000'],
            'sender' => ['nullable', 'string', 'max:255'],
            'recipient' => ['nullable', 'string', 'max:255'],
            'document_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date'],
            'security_level' => ['nullable', Rule::in(['PUBLIC', 'INTERNAL', 'RESTRICTED', 'CONFIDENTIAL', 'STRICTLY_CONFIDENTIAL'])],
            'status' => ['nullable', Rule::in(['DRAFT', 'PENDING', 'VERIFIED', 'ACTIVE', 'INACTIVE'])],
            'retention_start_date' => ['nullable', 'date'],
            'active_retention_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'inactive_retention_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'final_action' => ['nullable', Rule::in(['REVIEW', 'PERMANENT', 'TRANSFER', 'DESTROY'])],
            'keywords' => ['nullable', 'string', 'max:1000'],
            'physical_location_note' => ['nullable', 'string', 'max:1000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:'.env('MAX_ARCHIVE_UPLOAD_KB', 20480)],
        ]);
    }

    private function nextArchiveNumber(string $type, int $classificationId): string
    {
        $classCode = DB::table('archive_classifications')->where('id', $classificationId)->value('code') ?? 'UMUM';
        $prefix = match ($type) { 'INCOMING' => 'SM', 'OUTGOING' => 'SK', default => 'ARS' };
        $year = now()->format('Y');
        $count = Archive::query()->whereYear('created_at', $year)->lockForUpdate()->count() + 1;
        return sprintf('%s/%s/%s/%06d', $prefix, Str::upper($classCode), $year, $count);
    }

    private function storeVersion(Archive $archive, $file, ?string $note, int $userId, bool $isFinal)
    {
        $next = ((int) $archive->versions()->max('version_number')) + 1;
        $extension = strtolower($file->getClientOriginalExtension());
        $safeName = Str::uuid().'.'.$extension;
        $directory = sprintf('private/archives/%s/%s', now()->format('Y'), $archive->id);
        $path = $file->storeAs($directory, $safeName, 'local');
        if ($isFinal) $archive->versions()->update(['is_final' => false]);
        return $archive->versions()->create([
            'version_number' => $next,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'checksum_sha256' => hash_file('sha256', $file->getRealPath()),
            'change_note' => $note,
            'uploaded_by' => $userId,
            'is_final' => $isFinal,
        ]);
    }

    private function authorizeArchive(Request $request, Archive $archive, bool $write = false): void
    {
        $user = $request->user();
        $allowed = in_array($user->role, ['super_admin', 'auditor'], true) || $archive->unit_id === $user->unit_id;
        if ($write && $user->role === 'auditor') $allowed = false;
        abort_unless($allowed, 403, 'Tidak memiliki akses ke arsip ini.');
    }

    private function audit(int $userId, string $action, int $archiveId, Request $request): void
    {
        DB::table('activity_logs')->insert([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => 'archive',
            'subject_id' => $archiveId,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'created_at' => now(),
        ]);
    }
}
