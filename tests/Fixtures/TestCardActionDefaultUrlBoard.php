<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Filament\Actions\Action;

/**
 * A card action with no ->url() of its own, on a page whose getDefaultActionUrl()
 * returns a url. Filament's resource pages do exactly this for modal-less
 * Create/Edit/View actions, so the card must keep mounting the action rather than
 * silently becoming a link.
 */
class TestCardActionDefaultUrlBoard extends TestCardActionBoard
{
    public function getDefaultActionUrl(Action $action): ?string
    {
        return 'https://example.test/default-action-url';
    }

    protected function cardActionName(): string
    {
        return 'run';
    }
}
