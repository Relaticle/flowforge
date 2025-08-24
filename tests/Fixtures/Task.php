<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Relaticle\Flowforge\Database\Factories\TaskFactory;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'status',
        'priority',
        'order_position',
        'project_id',
        'assigned_to',
        'created_by',
        'description',
        'estimated_hours',
        'actual_hours',
        'labels',
        'due_date',
        'completed_at',
    ];

    protected $casts = [
        'labels' => 'array',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected $table = 'tasks';

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function newFactory(): TaskFactory
    {
        return TaskFactory::new();
    }
}
