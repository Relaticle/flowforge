<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

use Filament\Actions\Action;

/**
 * A card action with no ->url() of its own, on a page whose getDefaultActionUrl()
 * returns a url. Filament's resource pages do this for modal-less Create/Edit/View
 * actions when the resource has the matching page. Filament's own table honours that
 * fallback (its recordUrl() closure reads getUrl(), which consults it), so the card
 * does too.
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
