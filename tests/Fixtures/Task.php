<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'title',
        'status',
        'priority',
        'order_position',
    ];

    protected $table = 'tasks';
}
