<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class MaintenanceRequest extends Model
{
    public const STATUS_PENDING_BACKUP = 'pending_backup';
    public const STATUS_BACKUP_CONFIRMED = 'backup_confirmed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'requested_by',
        'technician_id',
        'type',
        'status',
        'backup_filename',
        'backup_checksum',
        'backup_size',
        'backup_confirmed_at',
        'approved_by',
        'approved_at',
        'executed_by',
        'started_at',
        'finished_at',
        'description',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'backup_size' => 'integer',
            'backup_confirmed_at' => 'datetime',
            'approved_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    public function backupIsConfirmed(): bool
    {
        return $this->backup_confirmed_at !== null
            && filled($this->backup_checksum);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ], true);
    }
        public function isJefe(): bool
    {
        return mb_strtolower((string) $this->role?->name) === 'jefe';
    }

    public function isTecnico(): bool
    {
        return mb_strtolower((string) $this->role?->name) === 'tecnico';
    }
}
