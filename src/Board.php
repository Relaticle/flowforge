<?php

namespace Relaticle\Flowforge;

use Filament\Support\Components\ViewComponent;
use Relaticle\Flowforge\Concerns\HasRecordAction;
use Relaticle\Flowforge\Concerns\HasRecordActions;

class Board  extends ViewComponent
{
    use HasRecordAction;
    use HasRecordActions;

}
