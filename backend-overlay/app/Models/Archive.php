<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Archive extends Model
{
    protected $fillable = [
        'unit_id', 'classification_id', 'location_id', 'archive_number', 'agenda_number',
        'document_number', 'type', 'title', 'subject', 'sender', 'recipient', 'document_date',
        'received_date', 'security_level', 'status', 'retention_start_date', 'active_retention_years',
        'inactive_retention_years', 'final_action', 'keywords', 'physical_location_note',
        'created_by', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'received_date' => 'date',
            'retention_start_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ArchiveVersion::class)->latest('version_number');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (in_array($user->role, ['super_admin', 'auditor'], true)) {
            return $query;
        }
        return $query->where('unit_id', $user->unit_id);
    }
}
