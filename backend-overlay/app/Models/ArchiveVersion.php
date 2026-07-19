<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchiveVersion extends Model
{
    protected $fillable = [
        'archive_id', 'version_number', 'file_path', 'original_name', 'mime_type',
        'file_size', 'checksum_sha256', 'change_note', 'uploaded_by', 'is_final',
    ];

    protected function casts(): array
    {
        return ['is_final' => 'boolean'];
    }
}
