<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Tests\Fixtures;

/**
 * A card action carrying both ->url() and ->requiresConfirmation(). The whole card is a
 * large accidental-click target, so the confirmation must win over the url.
 */
class TestCardActionConfirmBoard extends TestCardActionBoard
{
    protected function cardActionName(): string
    {
        return 'urlWithConfirmation';
    }
}
