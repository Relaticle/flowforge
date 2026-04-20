<?php

namespace Relaticle\Flowforge\Tests\Fixtures;

enum StatusEnum: string
{
    case ToDo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Testing = 'testing';
    case Done = 'done';
    case Blocked = 'blocked';
    case SoloColumn = 'solo_column';
    case Cramped = 'cramped';
}
